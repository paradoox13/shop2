<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\Request;
use App\Support\Auth;

final class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request): void
    {
        if (Auth::check()) {
            \App\Http\Response::redirect(Auth::homeUrl());
        }
    }
}
