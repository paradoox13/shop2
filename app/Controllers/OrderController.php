<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\NotFoundException;
use App\Http\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Auth;
use App\Support\Session;

final class OrderController extends Controller
{
    public function show(string $tracking): string
    {
        $order = Order::findByTracking($tracking);

        if ($order === null) {
            throw new NotFoundException('سفارش مورد نظر یافت نشد.');
        }

        $access = Session::get('order_access', []);
        $allowed = Auth::isStaff()
            || (is_array($access) && in_array($tracking, $access, true))
            || (Auth::check() && (int) $order['user_id'] === Auth::id());

        if (!$allowed) {
            throw new NotFoundException('برای مشاهده این سفارش از لینک ارسال‌شده استفاده کنید.');
        }

        $items = Order::items((int) $order['id']);
        $payments = Payment::byOrder((int) $order['id']);

        return $this->view('pages/order', [
            'title' => 'سفارش ' . $tracking . ' | ' . setting('site_name'),
            'metaDescription' => 'وضعیت سفارش ' . $tracking,
            'order' => $order,
            'items' => $items,
            'payments' => $payments,
            'canRetry' => in_array((string) $order['status'], ['pending', 'failed', 'canceled'], true),
        ], 'main');
    }
}
