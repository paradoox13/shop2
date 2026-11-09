<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\Request;
use App\Support\Auth;

final class ResellerMiddleware implements MiddlewareInterface
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            \App\Http\Response::redirect(url('/reseller/login'));
        }

        if (Auth::isStaff()) {
            \App\Http\Response::redirect(url('/admin'));
        }

        if (Auth::role() !== 'reseller') {
            \App\Http\Response::redirect(url('/'));
        }
    }
}
