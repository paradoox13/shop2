<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

/**
 * اعتبارسنج ساده و بدون وابستگی.
 * پشتیبانی از قواعد: required, nullable, string, integer, numeric, array, boolean,
 * min, max, between, email, url, mobile, in, not_in, regex, date, jalali_date,
 * same, confirmed, unique, exists
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $data;

    /** @param array<string, mixed> $data @param array<string, mixed> $rules @param array<string, string> $labels */
    private function __construct(array $data, private readonly array $rules, private readonly array $labels = [])
    {
        $this->data = $data;
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $rules @param array<string, string> $labels */
    public static function make(array $data, array $rules, array $labels = []): self
    {
        $validator = new self($data, $rules, $labels);
        $validator->run();

        return $validator;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return array<string, mixed> */
    public function validated(): array
    {
        return $this->data;
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $rules) {
            $rules = is_array($rules) ? $rules : explode('|', (string) $rules);
            $value = $this->data[$field] ?? null;
            $label = $this->labels[$field] ?? $field;

            $nullable = in_array('nullable', $rules, true);

            if (!$this->filled($value)) {
                if (in_array('required', $rules, true)) {
                    $this->errors[$field] = sprintf('%s الزامی است.', $label);
                } elseif (!$nullable) {
                    continue;
                } else {
                    continue;
                }

                continue;
            }

            foreach ($rules as $rule) {
                if (in_array($rule, ['required', 'nullable'], true)) {
                    continue;
                }

                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
                $error = $this->check((string) $name, $value, $parameter, $field, $label, $rules);

                if ($error !== null) {
                    $this->errors[$field] = $error;
                    break;
                }
            }
        }
    }

    /** @param array<int, string> $rules */
    private function check(string $rule, mixed $value, ?string $parameter, string $field, string $label, array $rules): ?string
    {
        return match ($rule) {
            'string' => is_string($value) || is_numeric($value) ? null : sprintf('%s باید متن باشد.', $label),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false ? null : sprintf('%s باید عدد صحیح باشد.', $label),
            'numeric' => is_numeric($value) ? null : sprintf('%s باید عدد باشد.', $label),
            'array' => is_array($value) ? null : sprintf('%s باید لیست باشد.', $label),
            'boolean' => in_array((string) $value, ['0', '1', 'true', 'false', 'on'], true) ? null : sprintf('%s نامعتبر است.', $label),
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? null : 'ایمیل وارد شده معتبر نیست.',
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false ? null : 'آدرس وارد شده معتبر نیست.',
            'mobile' => Str::normalizeMobile((string) $value) !== null ? null : 'شماره موبایل معتبر نیست (مثال: 09121234567).',
            'min' => $this->checkMinMax($value, (float) $parameter, $rules, true, $label),
            'max' => $this->checkMinMax($value, (float) $parameter, $rules, false, $label),
            'between' => $this->checkBetween($value, (string) $parameter, $label),
            'in' => in_array((string) $value, array_map('trim', explode(',', (string) $parameter)), true) ? null : sprintf('%s انتخاب‌شده معتبر نیست.', $label),
            'not_in' => !in_array((string) $value, array_map('trim', explode(',', (string) $parameter)), true) ? null : sprintf('%s انتخاب‌شده مجاز نیست.', $label),
            'regex' => $this->checkRegex((string) $value, (string) $parameter, $label),
            'date' => $this->checkDate((string) $value) ? null : 'تاریخ وارد شده معتبر نیست.',
            'jalali_date' => Jalali::parse((string) $value) !== null ? null : 'تاریخ شمسی وارد شده معتبر نیست (مثال: 1370/05/12).',
            'same' => (string) ($this->data[(string) $parameter] ?? '') === (string) $value ? null : sprintf('%s با %s یکسان نیست.', $label, $this->labels[(string) $parameter] ?? $parameter),
            'confirmed' => (string) ($this->data[$field . '_confirmation'] ?? '') === (string) $value ? null : sprintf('تکرار %s مطابقت ندارد.', $label),
            'unique' => $this->checkUnique($value, (string) $parameter, $field, $label),
            'exists' => $this->checkExists($value, (string) $parameter, $label),
            default => null,
        };
    }

    /** @param array<int, string> $rules */
    private function checkMinMax(mixed $value, float $limit, array $rules, bool $isMin, string $label): ?string
    {
        $numeric = in_array('integer', $rules, true) || in_array('numeric', $rules, true);

        if ($numeric) {
            $valid = $isMin ? (float) $value >= $limit : (float) $value <= $limit;
            if (!$valid) {
                return sprintf('%s باید %s %s باشد.', $label, $isMin ? 'حداقل' : 'حداکثر', fa_num($limit));
            }

            return null;
        }

        $length = mb_strlen((string) $value, 'UTF-8');
        $valid = $isMin ? $length >= $limit : $length <= $limit;

        return $valid ? null : sprintf('%s باید %s %s کاراکتر باشد.', $label, $isMin ? 'حداقل' : 'حداکثر', fa_num($limit));
    }

    private function checkBetween(mixed $value, string $parameter, string $label): ?string
    {
        [$min, $max] = array_pad(explode(',', $parameter), 2, null);

        if (!is_numeric($min) || !is_numeric($max)) {
            return null;
        }

        if (!is_numeric($value) || (float) $value < (float) $min || (float) $value > (float) $max) {
            return sprintf('%s باید بین %s و %s باشد.', $label, fa_num($min), fa_num($max));
        }

        return null;
    }

    private function checkRegex(string $value, string $pattern, string $label): ?string
    {
        if ($pattern === '' || @preg_match($pattern, '') === false) {
            return null;
        }

        return preg_match($pattern, $value) === 1 ? null : sprintf('قالب %s معتبر نیست.', $label);
    }

    private function checkDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function checkUnique(mixed $value, string $parameter, string $field, string $label): ?string
    {
        [$table, $column, $ignoreId, $idColumn] = array_pad(explode(',', $parameter), 4, null);

        if ($table === null || $column === null || !$this->identifier($table) || !$this->identifier($column)) {
            return null;
        }

        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = ?";
        $params = [$value];

        if ($ignoreId !== null && $ignoreId !== '' && $idColumn !== null && $this->identifier($idColumn)) {
            $sql .= " AND `{$idColumn}` <> ?";
            $params[] = $ignoreId;
        }

        $count = (int) Db::scalar($sql, $params, 0);

        return $count > 0 ? sprintf('این %s قبلاً ثبت شده است.', $label) : null;
    }

    private function checkExists(mixed $value, string $parameter, string $label): ?string
    {
        [$table, $column] = array_pad(explode(',', $parameter), 2, null);

        if ($table === null || $column === null || !$this->identifier($table) || !$this->identifier($column)) {
            return null;
        }

        $count = (int) Db::scalar("SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = ?", [$value], 0);

        return $count === 0 ? sprintf('%s انتخاب‌شده یافت نشد.', $label) : null;
    }

    private function identifier(string $value): bool
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $value) === 1;
    }

    private function filled(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return true;
    }
}
