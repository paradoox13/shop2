<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Exceptions\NotFoundException;
use App\Http\Controller;
use App\Http\Request;
use App\Models\Operator;
use App\Models\PhoneNumber;
use App\Models\User;
use App\Support\Audit;
use App\Support\Jalali;
use App\Support\Str;
use RuntimeException;

final class NumberController extends Controller
{
    public function index(Request $request): string
    {
        $this->requirePermission('numbers.manage');
        $page = max(1, $request->int('page', 1));
        $filters = $request->all();
        unset($filters['page'], $filters['_token']);

        return $this->view('admin/numbers/index', [
            'title' => 'مدیریت شماره‌ها',
            'numbers' => PhoneNumber::search($filters, $page, (int) config('pagination.admin', 20), null, false),
            'filters' => $filters,
            'operators' => Operator::all(),
            'resellers' => User::resellersForSelect(),
        ], 'admin');
    }

    public function create(): string
    {
        $this->requirePermission('numbers.manage');

        return $this->view('admin/numbers/form', [
            'title' => 'افزودن شماره',
            'number' => null,
            'operators' => Operator::all(),
            'resellers' => User::resellersForSelect(),
            'action' => url('/admin/numbers/store'),
        ], 'admin');
    }

    public function store(Request $request): string
    {
        $this->requirePermission('numbers.manage');
        $data = $this->validated($request);

        try {
            $id = PhoneNumber::create([
                'number' => $data['number'],
                'operator_id' => (int) $data['operator_id'],
                'price' => (int) $data['price'],
                'status' => (string) $data['status'],
                'reseller_id' => (int) ($data['reseller_id'] ?? 0),
                'number_type' => (string) $data['number_type'],
                'line_type' => (string) $data['line_type'],
                'birth_date' => $data['birth_date'] ? Jalali::parse((string) $data['birth_date']) : null,
                'description' => (string) ($data['description'] ?? ''),
            ]);
            Audit::log('number.created', 'phone_numbers', (string) $id, ['number' => $data['number']]);
            flash('success', 'شماره با موفقیت ثبت شد.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->back('/admin/numbers/create');
        }

        $this->redirect('/admin/numbers');
        return '';
    }

    public function edit(int $id): string
    {
        $this->requirePermission('numbers.manage');
        $number = PhoneNumber::find($id);

        if ($number === null) {
            throw new NotFoundException('شماره یافت نشد.');
        }

        return $this->view('admin/numbers/form', [
            'title' => 'ویرایش شماره ' . $number['number'],
            'number' => $number,
            'operators' => Operator::all(),
            'resellers' => User::resellersForSelect(),
            'action' => url('/admin/numbers/' . $id . '/update'),
        ], 'admin');
    }

    public function update(Request $request, int $id): string
    {
        $this->requirePermission('numbers.manage');
        $number = PhoneNumber::find($id);

        if ($number === null) {
            throw new NotFoundException('شماره یافت نشد.');
        }

        $data = $this->validated($request);

        try {
            PhoneNumber::update($id, [
                'number' => $data['number'],
                'operator_id' => (int) $data['operator_id'],
                'price' => (int) $data['price'],
                'status' => (string) $data['status'],
                'reseller_id' => (int) ($data['reseller_id'] ?? 0),
                'number_type' => (string) $data['number_type'],
                'line_type' => (string) $data['line_type'],
                'birth_date' => (string) ($data['birth_date'] ?? ''),
                'description' => (string) ($data['description'] ?? ''),
            ]);
            Audit::log('number.updated', 'phone_numbers', (string) $id, ['number' => $data['number']]);
            flash('success', 'شماره به‌روزرسانی شد.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->back('/admin/numbers/' . $id . '/edit');
        }

        $this->redirect('/admin/numbers');
        return '';
    }

    public function delete(Request $request, int $id): string
    {
        $this->requirePermission('numbers.manage');
        $number = PhoneNumber::find($id);

        if ($number === null) {
            flash('error', 'شماره یافت نشد.');
            $this->redirect('/admin/numbers');
        }

        if ($number['status'] === 'sold') {
            flash('error', 'شماره فروخته‌شده قابل حذف نیست (سابقه سفارش حفظ می‌شود).');
            $this->redirect('/admin/numbers');
        }

        PhoneNumber::delete($id);
        Audit::log('number.deleted', 'phone_numbers', (string) $id, ['number' => $number['number']]);
        flash('success', 'شماره حذف شد.');
        $this->redirect('/admin/numbers');

        return '';
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $this->validate($request, [
            'number' => 'required|mobile',
            'operator_id' => 'required|integer|exists:operators,id',
            'price' => 'required|integer|min:1000',
            'status' => 'required|in:available,reserved,sold,hidden',
            'reseller_id' => 'nullable|integer|exists:users,id',
            'number_type' => 'required|in:round,family,super_round,serial,birthday,other',
            'line_type' => 'required|in:prepaid,permanent',
            'birth_date' => 'nullable|jalali_date',
            'description' => 'nullable|max:500',
        ], [
            'number' => 'شماره',
            'operator_id' => 'اپراتور',
            'price' => 'قیمت',
            'status' => 'وضعیت',
            'reseller_id' => 'نماینده',
            'number_type' => 'دسته‌بندی شماره',
            'line_type' => 'نوع خط',
            'birth_date' => 'تاریخ تولد',
            'description' => 'توضیحات',
        ]);

        $normalized = Str::normalizeMobile((string) $data['number']);

        if ($normalized === null) {
            flash('error', 'شماره موبایل معتبر نیست.');
            $this->back('/admin/numbers');
        }

        $data['number'] = $normalized;

        return $data;
    }
}
