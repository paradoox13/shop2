<?php

declare(strict_types=1);

namespace App\Controllers\Reseller;

use App\Http\Controller;
use App\Models\Modem;
use App\Models\OrderItem;
use App\Models\PhoneNumber;
use App\Models\User;
use App\Services\ReportService;
use App\Support\Auth;

final class DashboardController extends Controller
{
    public function index(): string
    {
        $id = (int) Auth::id();
        $profile = User::resellerProfile($id);
        $summary = (new ReportService())->resellerSummary($id);
        $available = PhoneNumber::search(['status' => 'available'], 1, 1, $id, false);

        return $this->view('reseller/dashboard', [
            'title' => 'پنل نماینده',
            'profile' => $profile,
            'summary' => $summary,
            'availableNumbers' => $available['total'],
            'totalNumbers' => PhoneNumber::search([], 1, 1, $id, false)['total'],
            'modemsCount' => Modem::search([], 1, 1, $id, false)['total'],
            'recentSales' => OrderItem::salesForReseller($id, [], 1, 6),
            'sold' => PhoneNumber::soldSummary($id),
        ], 'reseller');
    }
}
