<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Exceptions\NotFoundException;
use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Models\ImportLog;
use App\Services\ImportService;
use RuntimeException;
use Throwable;

final class ImportController extends Controller
{
    public function index(Request $request): string
    {
        $this->requirePermission('numbers.manage');

        return $this->view('admin/import/index', [
            'title' => 'ایمپورت اکسل / CSV',
            'logs' => ImportLog::forAdmin(max(1, $request->int('page', 1))),
        ], 'admin');
    }

    public function upload(Request $request): string
    {
        $this->requirePermission('numbers.manage');
        $type = $request->string('type');
        $file = $request->file('file');

        if (!in_array($type, ['number', 'modem'], true)) {
            flash('error', 'نوع ایمپورت را انتخاب کنید.');
            $this->redirect('/admin/import');
        }

        if ($file === null) {
            flash('error', 'فایلی انتخاب نشده است.');
            $this->redirect('/admin/import');
        }

        try {
            $result = (new ImportService())->upload(null, $type, $file);
            flash('success', 'فایل دریافت شد؛ پردازش را شروع کنید.');
            $this->redirect('/admin/import/' . $result['id']);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->redirect('/admin/import');
        }

        return '';
    }

    public function show(int $id): string
    {
        $this->requirePermission('numbers.manage');
        $log = ImportLog::find($id);

        if ($log === null) {
            throw new NotFoundException('ایمپورت یافت نشد.');
        }

        return $this->view('admin/import/show', [
            'title' => 'پردازش ایمپورت #' . $id,
            'log' => $log,
            'progress' => (new ImportService())->progress($log),
        ], 'admin');
    }

    /** @return array<string, mixed> */
    public function process(int $id): array
    {
        $this->requirePermission('numbers.manage');
        $log = ImportLog::find($id);

        if ($log === null) {
            Response::json(['error' => 'ایمپورت یافت نشد.'], 404);
        }

        try {
            return (new ImportService())->processChunk($id, 100, null);
        } catch (Throwable $e) {
            \App\Support\Logger::error('Admin import chunk failed', ['import_id' => $id, 'error' => $e->getMessage()]);

            return [
                'status' => 'error',
                'processed' => (int) $log['processed_rows'],
                'total' => (int) $log['total_rows'],
                'inserted' => (int) $log['inserted_rows'],
                'failed' => (int) $log['failed_rows'],
                'percent' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function template(string $type): string
    {
        $this->requirePermission('numbers.manage');

        if (!in_array($type, ['number', 'modem'], true)) {
            throw new NotFoundException('قالب یافت نشد.');
        }

        $csv = (new ImportService())->csvTemplate($type);
        Response::download($csv, 'aval-sim-import-' . $type . '.csv');
    }
}
