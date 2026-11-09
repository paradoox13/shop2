<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\Request;

interface MiddlewareInterface
{
    public function handle(Request $request): void;
}
