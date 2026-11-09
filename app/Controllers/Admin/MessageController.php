<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Http\Controller;
use App\Http\Request;
use App\Models\ContactMessage;
use App\Support\Audit;

final class MessageController extends Controller
{
    public function index(Request $request): string
    {
        return $this->view('admin/messages/index', [
            'title' => 'پیام‌های تماس',
            'messages' => ContactMessage::paginate(max(1, $request->int('page', 1)), (int) config('pagination.admin', 20)),
        ], 'admin');
    }

    public function markRead(int $id): string
    {
        ContactMessage::markRead($id);
        Audit::log('contact.read', 'contact_messages', (string) $id);
        flash('success', 'پیام خوانده‌شده علامت‌گذاری شد.');
        $this->redirect('/admin/messages');

        return '';
    }
}
