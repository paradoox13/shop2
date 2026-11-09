<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Exceptions\NotFoundException;
use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\ReportService;
use App\Support\Audit;

final class OrderController extends Controller
{
    public function index(Request $request): string
    {
        $this->requirePermission('orders.view');
        $page = max(1, $request->int('page', 1));
        $filters = $request->all();

        return $this->view('admin/orders/index', [
            'title' => 'سفارش‌ها',
            'orders' => Order::adminSearch($filters, $page, (int) config('pagination.admin', 20)),
            'filters' => $filters,
            'stats' => Order::stats(),
        ], 'admin');
    }

    public function show(int $id): string
    {
        $this->requirePermission('orders.view');
        $order = Order::find($id);

        if ($order === null) {
            throw new NotFoundException('سفارش یافت نشد.');
        }

        $current = OrderStatus::tryFrom((string) $order['status']);
        $allowed = [];

        foreach (OrderStatus::cases() as $case) {
            if ($current !== null && $current->canTransitionTo($case)) {
                $allowed[] = $case;
            }
        }

        return $this->view('admin/orders/show', [
            'title' => 'سفارش ' . $order['tracking_code'],
            'order' => $order,
            'items' => Order::items($id),
            'payments' => Payment::byOrder($id),
            'allowedStatuses' => $allowed,
            'reservations' => Reservation::forOrder($id),
        ], 'admin');
    }

    public function updateStatus(Request $request, int $id): string
    {
        $this->requirePermission('orders.manage');
        $order = Order::find($id);

        if ($order === null) {
            throw new NotFoundException('سفارش یافت نشد.');
        }

        $data = $this->validate($request, [
            'status' => 'required|in:pending,awaiting_payment,paid,pending_physical_send,sent,completed,canceled,failed,refunded',
            'admin_note' => 'nullable|max:2000',
        ]);

        $current = OrderStatus::tryFrom((string) $order['status']);
        $next = OrderStatus::from((string) $data['status']);

        if ($current === null || !$current->canTransitionTo($next)) {
            flash('error', 'تغییر وضعیت از «' . order_status_label((string) $order['status']) . '» به «' . $next->label() . '» مجاز نیست.');
            $this->redirect('/admin/orders/' . $id);
        }

        if ($next === OrderStatus::Paid) {
            flash('error', 'وضعیت پرداخت‌شده فقط از طریق تأیید درگاه ثبت می‌شود.');
            $this->redirect('/admin/orders/' . $id);
        }

        if (in_array($next, [OrderStatus::Canceled, OrderStatus::Failed, OrderStatus::Refunded], true)) {
            Reservation::releaseForOrder($id);
        }

        Order::updateStatus($id, $next->value, isset($data['admin_note']) ? (string) $data['admin_note'] : null);
        Audit::log('order.status_changed', 'orders', (string) $id, ['from' => $order['status'], 'to' => $next->value]);
        flash('success', 'وضعیت سفارش به «' . $next->label() . '» تغییر کرد.');
        $this->redirect('/admin/orders/' . $id);

        return '';
    }

    public function export(Request $request): string
    {
        $this->requirePermission('reports.view');
        $rows = Order::forExport($request->all());
        $csv = (new ReportService())->csv($rows, [
            'id' => 'شناسه',
            'tracking_code' => 'کد رهگیری',
            'customer_name' => 'نام مشتری',
            'customer_phone' => 'موبایل',
            'customer_email' => 'ایمیل',
            'type' => 'نوع',
            'total_price' => 'مبلغ (تومان)',
            'status' => 'وضعیت',
            'created_at' => 'تاریخ ثبت',
        ]);

        Response::download($csv, 'orders-' . date('Ymd-His') . '.csv');
    }
}
