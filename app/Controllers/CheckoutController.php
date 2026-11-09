<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Controller;
use App\Http\Request;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Support\Auth;
use App\Support\Session;
use RuntimeException;
use Throwable;

final class CheckoutController extends Controller
{
    public function index(): string
    {
        $cart = CartService::items();

        if ($cart['count'] === 0) {
            flash('error', 'سبد خرید شما خالی است.');
            $this->redirect('/cart');
        }

        $user = Auth::user();

        return $this->view('pages/checkout', [
            'title' => 'تکمیل خرید | ' . setting('site_name'),
            'cart' => $cart,
            'user' => $user,
        ], 'main');
    }

    public function store(Request $request): string
    {
        $data = $this->validate($request, [
            'customer_name' => 'required|min:3|max:120',
            'customer_phone' => 'required|mobile',
            'customer_email' => 'nullable|email|max:190',
            'terms' => 'required|in:1,on,accepted',
        ], [
            'customer_name' => 'نام و نام خانوادگی',
            'customer_phone' => 'شماره موبایل',
            'customer_email' => 'ایمیل',
            'terms' => 'پذیرش قوانین',
        ]);

        $phone = \App\Support\Str::normalizeMobile((string) $data['customer_phone']);

        if ($phone === null) {
            flash('error', 'شماره موبایل معتبر نیست.');
            $this->back('/checkout');
        }

        try {
            $result = (new OrderService())->createFromCart([
                'customer_name' => (string) $data['customer_name'],
                'customer_phone' => $phone,
                'customer_email' => $data['customer_email'] ?: null,
            ], Auth::id());
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->back('/checkout');
            exit;
        }

        $this->grantAccess($result['tracking_code']);

        try {
            $payment = (new PaymentService())->initiate($result['order_id']);
            \App\Http\Response::redirectExternal($payment['redirect']);
        } catch (Throwable $e) {
            \App\Support\Logger::error('Payment init after checkout failed', [
                'order_id' => $result['order_id'],
                'error' => $e->getMessage(),
            ]);
            flash('error', 'سفارش ثبت شد اما اتصال به درگاه ناموفق بود: ' . $e->getMessage());
            $this->redirect('/order/' . $result['tracking_code']);
        }

        return '';
    }

    private function grantAccess(string $trackingCode): void
    {
        $access = Session::get('order_access', []);
        $access = is_array($access) ? $access : [];
        $access[] = $trackingCode;
        Session::put('order_access', array_values(array_unique($access)));
    }
}
