<?php

declare(strict_types=1);

namespace App\Exceptions;

final class NotFoundException extends HttpException
{
    public function __construct(string $message = 'صفحه مورد نظر یافت نشد.')
    {
        parent::__construct(404, $message);
    }
}
