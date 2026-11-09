<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Http\Controller;
use App\Models\ContactMessage;
use App\Models\Job;
use App\Models\Modem;
use App\Models\Order;
use App\Models\PhoneNumber;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\BackupService;

final class DashboardController extends Controller
{
    public function index(): string
    {
        $orderStats = Order::stats();

        return $this->view('admin/dashboard', [
            'title' => 'داشبورد مدیریت',
            'orderStats' => $orderStats,
            'availableNumbers' => PhoneNumber::countAvailable(),
            'resellersCount' => User::countByRole('reseller'),
            'lowStockModems' => Modem::lowStock(3),
            'chart' => Order::salesByDay(14),
            'recentOrders' => Order::recent(8),
            'smsStats' => SmsLog::stats(),
            'newMessages' => ContactMessage::countNew(),
            'pendingJobs' => Job::pendingCount(),
            'backups' => array_slice((new BackupService())->listBackups(), 0, 3),
        ], 'admin');
    }
}
