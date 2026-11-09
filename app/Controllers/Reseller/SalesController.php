<?php

declare(strict_types=1);

namespace App\Controllers\Reseller;

use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Models\OrderItem;
use App\Services\ReportService;
use App\Support\Auth;

final class SalesController extends Controller
{
    public function index(Request $request): string
    {
        $resellerId = (int) Auth::id();
        $filters = $request->all();
        $page = max(1, $request->int('page', 1));
        $summary = (new ReportService())->resellerSummary(
            $resellerId,
            $request->string('from'),
            $request->string('to')
        );

        return $this->view('reseller/sales/index', [
            'title' => 'گزارش فروش و تسویه',
            'sales' => OrderItem::salesForReseller($resellerId, $filters, $page, (int) config('pagination.admin', 20)),
            'summary' => $summary,
            'filters' => $filters,
        ], 'reseller');
    }

    public function export(Request $request): string
    {
        $resellerId = (int) Auth::id();
        $rows = OrderItem::allSalesForReseller($resellerId, $request->all());
        $csv = (new ReportService())->csv($rows, [
            'order_created_at' => 'تاریخ فروش',
            'order_id' => 'شناسه سفارش',
            'tracking_code' => 'کد رهگیری',
            'customer_name' => 'مشتری',
            'customer_phone' => 'موبایل',
            'product_type' => 'نوع محصول',
            'price' => 'قیمت واحد',
            'qty' => 'تعداد',
            'order_status' => 'وضعیت سفارش',
        ]);

        Response::download($csv, 'reseller-sales-' . date('Ymd-His') . '.csv');
    }
}
