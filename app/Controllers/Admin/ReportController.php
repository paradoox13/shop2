<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Models\Order;
use App\Services\ReportService;
use App\Support\Jalali;

final class ReportController extends Controller
{
    public function index(Request $request): string
    {
        $this->requirePermission('reports.view');
        $from = $request->string('from');
        $to = $request->string('to');
        $overview = (new ReportService())->salesOverview($from, $to);

        return $this->view('admin/reports/index', [
            'title' => 'گزارش فروش',
            'overview' => $overview,
            'from' => Jalali::format($overview['from']),
            'to' => Jalali::format($overview['to']),
        ], 'admin');
    }

    public function export(Request $request): string
    {
        $this->requirePermission('reports.view');
        $rows = Order::forExport($request->all());
        $csv = (new ReportService())->csv($rows, [
            'id' => 'شناسه',
            'tracking_code' => 'کد رهگیری',
            'customer_name' => 'مشتری',
            'customer_phone' => 'موبایل',
            'customer_email' => 'ایمیل',
            'type' => 'نوع',
            'total_price' => 'مبلغ',
            'status' => 'وضعیت',
            'created_at' => 'تاریخ',
        ]);

        Response::download($csv, 'report-' . date('Ymd-His') . '.csv');
    }
}
