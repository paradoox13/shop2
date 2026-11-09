<?php

declare(strict_types=1);

namespace App\Enums;

enum JobStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Done = 'done';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در صف',
            self::Processing => 'در حال اجرا',
            self::Done => 'انجام شده',
            self::Failed => 'ناموفق',
        };
    }
}
