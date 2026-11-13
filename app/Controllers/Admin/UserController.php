<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Exceptions\NotFoundException;
use App\Http\Controller;
use App\Http\Request;
use App\Models\User;
use App\Support\Audit;
use App\Support\Str;
use RuntimeException;

final class UserController extends Controller
{
    public function index(Request $request): string
    {
        $this->requirePermission('resellers.manage');
        $page = max(1, $request->int('page', 1));
        $search = trim($request->string('q'));

        return $this->view('admin/users/index', [
            'title' => 'نمایندگان',
            'resellers' => User::paginateResellers($search !== '' ? $search : null, $page, (int) config('pagination.admin', 20)),
            'search' => $search,
        ], 'admin');
    }

    public function create(): string
    {
        $this->requirePermission('resellers.manage');

        return $this->view('admin/users/form', [
            'title' => 'افزودن نماینده',
            'reseller' => null,
            'profile' => null,
            'action' => url('/admin/resellers/store'),
        ], 'admin');
    }

    public function store(Request $request): string
    {
        $this->requirePermission('resellers.manage');
        $data = $this->validate($request, [
            'username' => 'required|min:3|max:30|regex:/^[a-zA-Z0-9_]+$/|unique:users,username',
            'password' => 'required|min:8|max:72|confirmed',
            'full_name' => 'required|min:3|max:120',
            'phone' => 'required|mobile|unique:users,phone',
            'email' => 'nullable|email|max:190',
            'display_name' => 'required|min:2|max:120',
            'contact' => 'nullable|max:150',
            'commission_rate' => 'required|numeric|between:0,100',
        ], [
            'username' => 'نام کاربری',
            'password' => 'رمز عبور',
            'full_name' => 'نام کامل',
            'phone' => 'موبایل',
            'display_name' => 'نام نمایشی',
            'commission_rate' => 'درصد کمیسیون',
        ]);

        try {
            $id = User::createReseller([
                'username' => $data['username'],
                'password' => $data['password'],
                'full_name' => $data['full_name'],
                'phone' => Str::normalizeMobile((string) $data['phone']),
                'email' => !empty($data['email']) ? (string) $data['email'] : null,
                'display_name' => $data['display_name'],
                'contact' => $data['contact'] ?? null,
                'commission_rate' => (float) $data['commission_rate'],
            ]);
            User::flushResellerCache();
            Audit::log('reseller.created', 'users', (string) $id, ['username' => $data['username']]);
            flash('success', 'نماینده با موفقیت ساخته شد.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->back('/admin/resellers/create');
        }

        $this->redirect('/admin/resellers');
        return '';
    }

    public function edit(int $id): string
    {
        $this->requirePermission('resellers.manage');
        $reseller = User::find($id);

        if ($reseller === null || $reseller['role'] !== 'reseller') {
            throw new NotFoundException('نماینده یافت نشد.');
        }

        return $this->view('admin/users/form', [
            'title' => 'ویرایش نماینده',
            'reseller' => $reseller,
            'profile' => User::resellerProfile($id),
            'action' => url('/admin/resellers/' . $id . '/update'),
        ], 'admin');
    }

    public function update(Request $request, int $id): string
    {
        $this->requirePermission('resellers.manage');
        $reseller = User::find($id);

        if ($reseller === null || $reseller['role'] !== 'reseller') {
            throw new NotFoundException('نماینده یافت نشد.');
        }

        $data = $this->validate($request, [
            'full_name' => 'required|min:3|max:120',
            'phone' => 'required|mobile',
            'email' => 'nullable|email|max:190',
            'status' => 'required|in:active,suspended',
            'display_name' => 'required|min:2|max:120',
            'contact' => 'nullable|max:150',
            'commission_rate' => 'required|numeric|between:0,100',
            'password' => 'nullable|min:8|max:72|confirmed',
        ], [
            'full_name' => 'نام کامل',
            'phone' => 'موبایل',
            'status' => 'وضعیت',
            'display_name' => 'نام نمایشی',
            'commission_rate' => 'درصد کمیسیون',
            'password' => 'رمز عبور جدید',
        ]);

        try {
            User::update($id, [
                'full_name' => $data['full_name'],
                'phone' => Str::normalizeMobile((string) $data['phone']),
                'email' => !empty($data['email']) ? (string) $data['email'] : null,
                'status' => $data['status'],
            ]);

            if (!empty($data['password'])) {
                User::updatePassword($id, (string) $data['password']);
            }

            User::upsertResellerProfile(
                $id,
                (string) $data['display_name'],
                isset($data['contact']) ? (string) $data['contact'] : null,
                (float) $data['commission_rate']
            );
            User::flushResellerCache();
            Audit::log('reseller.updated', 'users', (string) $id, ['status' => $data['status']]);
            flash('success', 'اطلاعات نماینده ذخیره شد.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->back('/admin/resellers/' . $id . '/edit');
        }

        $this->redirect('/admin/resellers');
        return '';
    }
}
