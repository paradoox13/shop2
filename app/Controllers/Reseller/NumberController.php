<?php

declare(strict_types=1);

namespace App\Controllers\Reseller;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Http\Controller;
use App\Http\Request;
use App\Models\Operator;
use App\Models\PhoneNumber;
use App\Support\Audit;
use App\Support\Auth;
use App\Support\Jalali;
use App\Support\Str;
use RuntimeException;

final class NumberController extends Controller
{
    public function index(Request $request): string
    {
        $resellerId = (int) Auth::id();
        $page = max(1, $request->int('page', 1));
        $filters = $request->all();

        return $this->view('reseller/numbers/index', [
            'title' => 'شماره‌های من',
            'numbers' => PhoneNumber::search($filters, $page, (int) config('pagination.admin', 20), $resellerId, false),
            'filters' => $filters,
            'operators' => Operator::all(),
        ], 'reseller');
    }

    public function create(): string
    {
        return $this->view('reseller/numbers/form', [
            'title' => 'ثبت شماره جدید',
            'number' => null,
            'operators' => Operator::all(),
            'action' => url('/reseller/numbers/store'),
        ], 'reseller');
    }

    public function store(Request $request): string
    {
        $data = $this->validated($request);

        try {
            $id = PhoneNumber::create([
                'operator_id' => (int) $data['operator_id'],
                'number' => (string) $data['number'],
                'price' => (int) $data['price'],
                'birth_date' => !empty($data['birth_date']) ? Jalali::parse((string) $data['birth_date']) : null,
                'description' => (string) ($data['description'] ?? ''),
                'reseller_id' => (int) Auth::id(),
                'status' => 'available',
            ]);
            Audit::log('reseller.number_created', 'phone_numbers', (string) $id, ['number' => $data['number']]);
            flash('success', 'شماره ثبت شد.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->back('/reseller/numbers/create');
        }

        $this->redirect('/reseller/numbers');
        return '';
    }

    public function edit(int $id): string
    {
        $number = $this->ownedAvailable($id);

        return $this->view('reseller/numbers/form', [
            'title' => 'ویرایش شماره ' . $number['number'],
            'number' => $number,
            'operators' => Operator::all(),
            'action' => url('/reseller/numbers/' . $id . '/update'),
        ], 'reseller');
    }

    public function update(Request $request, int $id): string
    {
        $number = $this->ownedAvailable($id);
        $data = $this->validated($request);

        try {
            PhoneNumber::update($id, [
                'operator_id' => (int) $data['operator_id'],
                'number' => (string) $data['number'],
                'price' => (int) $data['price'],
                'birth_date' => (string) ($data['birth_date'] ?? ''),
                'description' => (string) ($data['description'] ?? ''),
                'reseller_id' => (int) Auth::id(),
                'status' => $number['status'],
            ]);
            Audit::log('reseller.number_updated', 'phone_numbers', (string) $id);
            flash('success', 'شماره به‌روزرسانی شد.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->back('/reseller/numbers/' . $id . '/edit');
        }

        $this->redirect('/reseller/numbers');
        return '';
    }

    public function delete(Request $request, int $id): string
    {
        $number = $this->ownedAvailable($id);
        PhoneNumber::delete($id);
        Audit::log('reseller.number_deleted', 'phone_numbers', (string) $id, ['number' => $number['number']]);
        flash('success', 'شماره حذف شد.');
        $this->redirect('/reseller/numbers');

        return '';
    }

    /** @return array<string, mixed> */
    private function ownedAvailable(int $id): array
    {
        $number = PhoneNumber::find($id);

        if ($number === null) {
            throw new NotFoundException('شماره یافت نشد.');
        }

        if ((int) $number['reseller_id'] !== (int) Auth::id()) {
            throw new ForbiddenException('این شماره متعلق به شما نیست.');
        }

        if ($number['status'] !== 'available') {
            flash('error', 'فقط شماره‌های موجود قابل ویرایش یا حذف هستند.');
            $this->redirect('/reseller/numbers');
        }

        return $number;
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $this->validate($request, [
            'number' => 'required|mobile',
            'operator_id' => 'required|integer|exists:operators,id',
            'price' => 'required|integer|min:1000',
            'birth_date' => 'nullable|jalali_date',
            'description' => 'nullable|max:500',
        ], [
            'number' => 'شماره',
            'operator_id' => 'اپراتور',
            'price' => 'قیمت',
            'birth_date' => 'تاریخ تولد',
        ]);

        $normalized = Str::normalizeMobile((string) $data['number']);

        if ($normalized === null) {
            flash('error', 'شماره موبایل معتبر نیست.');
            $this->back('/reseller/numbers');
        }

        $data['number'] = $normalized;

        return $data;
    }
}
