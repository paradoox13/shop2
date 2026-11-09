<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\NotFoundException;
use App\Http\Controller;
use App\Models\Modem;
use App\Models\PhoneNumber;
use App\Support\Auth;

final class ProductController extends Controller
{
    public function number(int $id): string
    {
        $number = PhoneNumber::find($id);

        if ($number === null) {
            throw new NotFoundException('شماره مورد نظر یافت نشد.');
        }

        if ($number['status'] !== 'available' && !Auth::isStaff()) {
            throw new NotFoundException('این شماره در حال حاضر قابل خرید نیست.');
        }

        PhoneNumber::incrementViews($id);
        $related = PhoneNumber::search(['operator' => (string) $number['operator_id'], 'sort' => 'newest'], 1, 4, null, true);

        return $this->view('pages/number', [
            'title' => 'شماره ' . $number['number'] . ' | ' . setting('site_name'),
            'metaDescription' => 'خرید شماره سیم‌کارت ' . $number['operator_name'] . ' ' . $number['number'],
            'number' => $number,
            'related' => $related,
        ], 'main');
    }

    public function modem(int $id): string
    {
        $modem = Modem::find($id);

        if ($modem === null || ($modem['status'] !== 'active' && !Auth::isStaff())) {
            throw new NotFoundException('مودم مورد نظر یافت نشد.');
        }

        return $this->view('pages/modem', [
            'title' => $modem['brand'] . ' ' . $modem['model'] . ' | ' . setting('site_name'),
            'metaDescription' => truncate($modem['description'], 150),
            'modem' => $modem,
        ], 'main');
    }
}
