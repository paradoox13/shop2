<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\Request;
use App\Support\Auth;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            $next = urlencode($request->path() . (($request->all() !== []) ? '?' . http_build_query($request->all()) : ''));
            \App\Http\Response::redirect(url('/login?next=' . $next));
        }
    }
}
