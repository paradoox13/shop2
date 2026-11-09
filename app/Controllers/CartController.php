<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Controller;
use App\Http\Request;
use App\Services\CartService;
use RuntimeException;

final class CartController extends Controller
{
    public function index(): string
    {
        $cart = CartService::items();

        return $this->view('pages/cart', [
            'title' => 'سبد خرید | ' . setting('site_name'),
            'cart' => $cart,
        ], 'main');
    }

    public function addNumber(Request $request): string
    {
        try {
            CartService::addNumber($request->int('id'));
            flash('success', 'شماره به سبد خرید اضافه شد.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }

        $this->back('/');
        return '';
    }

    public function addModem(Request $request): string
    {
        try {
            CartService::addModem($request->int('id'), max(1, $request->int('qty', 1)));
            flash('success', 'مودم به سبد خرید اضافه شد.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }

        $this->back('/');
        return '';
    }

    public function addActivation(Request $request): string
    {
        try {
            CartService::setActivation($request->int('operator_id'), max(1, $request->int('qty', 1)));
            flash('success', 'کد فعال‌سازی به سبد خرید اضافه شد.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }

        $this->back('/');
        return '';
    }

    public function update(Request $request): string
    {
        $type = $request->string('type');
        $id = $request->int('id');
        $qty = $request->int('qty', 1);

        match ($type) {
            'modem' => CartService::updateModem($id, $qty),
            'activation' => CartService::setActivation($id, $qty),
            default => null,
        };

        flash('success', 'سبد خرید به‌روزرسانی شد.');
        $this->redirect('/cart');
        return '';
    }

    public function remove(Request $request): string
    {
        CartService::removeItem($request->string('type'), $request->int('id'));
        flash('success', 'آیتم از سبد خرید حذف شد.');
        $this->redirect('/cart');

        return '';
    }
}
