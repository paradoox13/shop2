<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Http\Controller;
use App\Http\Request;
use App\Models\SmsLog;
use App\Services\SmsService;
use App\Support\Audit;

final class SmsController extends Controller
{
    public function index(Request $request): string
    {
        $this->requirePermission('sms.view');
        $page = max(1, $request->int('page', 1));

        return $this->view('admin/sms/index', [
            'title' => 'پیامک‌ها',
            'logs' => SmsLog::paginate($request->string('status'), $request->string('q'), $page, (int) config('pagination.admin', 20)),
            'filters' => ['status' => $request->string('status'), 'q' => $request->string('q')],
            'stats' => SmsLog::stats(),
        ], 'admin');
    }

    public function retry(int $id): string
    {
        $this->requirePermission('sms.manage');
        $service = new SmsService();

        if ($service->retry($id)) {
            Audit::log('sms.retry', 'sms_logs', (string) $id);
            flash('success', 'پیامک برای ارسال مجدد در صف قرار گرفت.');
        } else {
            flash('error', 'این پیامک قابل ارسال مجدد نیست.');
        }

        $this->redirect('/admin/sms');
        return '';
    }
}
