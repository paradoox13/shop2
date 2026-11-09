<?php

declare(strict_types=1);

namespace App\Controllers\Reseller;

use App\Exceptions\NotFoundException;
use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Models\ImportLog;
use App\Services\ImportService;
use App\Support\Auth;
use RuntimeException;
use Throwable;

final class ImportController extends Controller
{
    public function index(Request $request): string
    {
        $resellerId = (int) Auth::id();

        return $this->view('reseller/import/index', [
            'title' => 'ایمپورت اکسل / CSV',
            'logs' => ImportLog::forReseller($resellerId, max(1, $request->int('page', 1))),
        ], 'reseller');
    }

    public function upload(Request $request): string
    {
        $type = $request->string('type');
        $file = $request->file('file');

        if (!in_array($type, ['number', 'modem'], true)) {
            flash('error', 'نوع ایمپورت را انتخاب کنید.');
            $this->redirect('/reseller/import');
        }

        if ($file === null) {
            flash('error', 'فایلی انتخاب نشده است.');
            $this->redirect('/reseller/import');
        }

        try {
            $result = (new ImportService())->upload((int) Auth::id(), $type, $file);
            flash('success', 'فایل دریافت شد؛ پردازش را شروع کنید.');
            $this->redirect('/reseller/import/' . $result['id']);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->redirect('/reseller/import');
        }

        return '';
    }

    public function show(int $id): string
    {
        $log = ImportLog::find($id, (int) Auth::id());

        if ($log === null) {
            throw new NotFoundException('ایمپورت یافت نشد.');
        }

        return $this->view('reseller/import/show', [
            'title' => 'پردازش ایمپورت #' . $id,
            'log' => $log,
            'progress' => (new ImportService())->progress($log),
        ], 'reseller');
    }

    /** @return array<string, mixed> */
    public function process(int $id): array
    {
        $log = ImportLog::find($id, (int) Auth::id());

        if ($log === null) {
            Response::json(['error' => 'ایمپورت یافت نشد.'], 404);
        }

        try {
            return (new ImportService())->processChunk($id, 100, (int) Auth::id());
        } catch (Throwable $e) {
            \App\Support\Logger::error('Import chunk failed', ['import_id' => $id, 'error' => $e->getMessage()]);

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
        if (!in_array($type, ['number', 'modem'], true)) {
            throw new NotFoundException('قالب یافت نشد.');
        }

        $csv = (new ImportService())->csvTemplate($type);
        Response::download($csv, 'aval-sim-import-' . $type . '.csv');
    }
}
