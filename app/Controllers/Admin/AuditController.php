<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Http\Controller;
use App\Http\Request;
use App\Support\Audit;

final class AuditController extends Controller
{
    public function index(Request $request): string
    {
        $this->requirePermission('audit.view');

        return $this->view('admin/audit/index', [
            'title' => 'لاگ عملیات',
            'logs' => Audit::recent(200, $request->string('action')),
            'filters' => ['action' => $request->string('action')],
        ], 'admin');
    }
}
