<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Http\Controller;
use App\Http\Request;
use App\Models\ActivationCodePrice;
use App\Support\Audit;

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
}
