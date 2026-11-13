<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Http\Controller;
use App\Http\Request;
use App\Models\ActivationCodePrice;
use App\Models\Operator;
use App\Support\Audit;
use RuntimeException;

final class ActivationController extends Controller
{
    public function index(): string
    {
        $this->requirePermission('activation.manage');

        return $this->view('admin/activation/index', [
            'title' => 'قیمت کد فعال‌سازی',
            'prices' => ActivationCodePrice::all(),
        ], 'admin');
    }

    public function update(Request $request): string
    {
        $this->requirePermission('activation.manage');
        $prices = $request->input('prices');
        $prices = is_array($prices) ? $prices : [];
        $clean = [];

        foreach ($prices as $operatorId => $price) {
            $operatorId = (int) $operatorId;

            if ($operatorId <= 0) {
                continue;
            }

            $price = (int) preg_replace('/\D+/', '', \App\Support\Str::toEnglishDigits((string) $price));

            if ($price < 0) {
                $price = 0;
            }

            $clean[$operatorId] = $price;
        }

        ActivationCodePrice::updatePrices($clean);
        Audit::log('activation.prices_updated', 'activation_code_prices', null, $clean);
        flash('success', 'قیمت‌های کد فعال‌سازی ذخیره شد.');
        $this->redirect('/admin/activation');

        return '';
    }

    public function storeOperator(Request $request): string
    {
        $this->requirePermission('activation.manage');

        $data = $this->validate($request, [
            'name' => 'required|min:2|max:60',
            'slug' => 'required|min:2|max:60|regex:/^[a-zA-Z0-9_-]+$/',
            'prefixes' => 'nullable|max:500',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ], [
            'name' => 'نام اپراتور',
            'slug' => 'شناسه اپراتور',
            'prefixes' => 'پیش‌شماره‌ها',
        ]);

        $patterns = preg_split('/[،,\s]+/u', (string) ($data['prefixes'] ?? '')) ?: [];

        try {
            $id = Operator::create(
                (string) $data['name'],
                (string) $data['slug'],
                $patterns,
                (int) ($data['sort_order'] ?? 0)
            );
            Audit::log('operator.created', 'operators', (string) $id, ['slug' => $data['slug']]);
            flash('success', 'اپراتور جدید اضافه شد. حالا قیمت کد فعال‌سازی آن را وارد کنید.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }

        $this->redirect('/admin/activation');

        return '';
    }
}
