<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Operator;
use App\Models\PhoneNumber;

/**
 * تشخیص اپراتور بر اساس پیش‌شماره؛ اپراتور با بلندترین پیش‌شماره منطبق برنده است.
 */
final class OperatorDetector
{
    /** @param array<int, array<string, mixed>>|null $operators */
    public function __construct(private ?array $operators = null)
    {
    }

    /** @return array<string, mixed>|null */
    public function detect(string $number): ?array
    {
        $normalized = PhoneNumber::normalizeNumber($number);

        if ($normalized === null) {
            return null;
        }

        $operators = $this->operators ?? Operator::all();
        $best = null;
        $bestLength = 0;

        foreach ($operators as $operator) {
            $patterns = $operator['patterns'] ?? Operator::decodePatterns((string) ($operator['prefix_patterns'] ?? '[]'));

            foreach ($patterns as $prefix) {
                $prefix = (string) $prefix;
                $length = strlen($prefix);

                if ($length > $bestLength && str_starts_with($normalized, $prefix)) {
                    $best = $operator;
                    $bestLength = $length;
                }
            }
        }

        return $best;
    }

    public function detectId(string $number): ?int
    {
        $operator = $this->detect($number);

        return $operator === null ? null : (int) $operator['id'];
    }
}
