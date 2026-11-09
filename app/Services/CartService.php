<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivationCodePrice;
use App\Models\Modem;
use App\Models\PhoneNumber;
use App\Support\Session;
use RuntimeException;

/**
 * سبد خرید مبتنی بر سشن. قیمت‌های نهایی هنگام ثبت سفارش از دیتابیس
 * دوباره خوانده و قفل می‌شوند؛ داده سشن هرگز مبنای مالی نیست.
 */
final class CartService
{
    private const KEY = 'cart';

    private const MAX_NUMBERS = 10;

    private const MAX_MODEM_QTY = 10;

    private const MAX_ACTIVATION_QTY = 10;

    /** @return array{numbers:array<int,int>, modems:array<int,int>, activation:array<int,int>} */
    public static function raw(): array
    {
        $cart = Session::get(self::KEY);
        $cart = is_array($cart) ? $cart : [];

        return [
            'numbers' => array_values(array_unique(array_map('intval', array_filter((array) ($cart['numbers'] ?? []))))),
            'modems' => array_map('intval', (array) ($cart['modems'] ?? [])),
            'activation' => array_map('intval', (array) ($cart['activation'] ?? [])),
        ];
    }

    private static function save(array $cart): void
    {
        $cart['modems'] = array_filter($cart['modems'], static fn (int $qty): bool => $qty > 0);
        $cart['activation'] = array_filter($cart['activation'], static fn (int $qty): bool => $qty > 0);

        Session::put(self::KEY, $cart);
    }

    public static function count(): int
    {
        $cart = self::raw();

        return count($cart['numbers'])
            + array_sum(array_map('intval', $cart['modems']))
            + array_sum(array_map('intval', $cart['activation']));
    }

    public static function isEmpty(): bool
    {
        $cart = self::raw();

        return $cart['numbers'] === [] && $cart['modems'] === [] && $cart['activation'] === [];
    }

    public static function addNumber(int $id): void
    {
        $number = PhoneNumber::find($id);

        if ($number === null || $number['status'] !== 'available') {
            throw new RuntimeException('این شماره در دسترس نیست.');
        }

        $cart = self::raw();

        if (in_array($id, $cart['numbers'], true)) {
            return;
        }

        if (count($cart['numbers']) >= self::MAX_NUMBERS) {
            throw new RuntimeException('حداکثر ' . self::MAX_NUMBERS . ' شماره در هر سفارش قابل خرید است.');
        }

        $cart['numbers'][] = $id;
        self::save($cart);
    }

    public static function removeNumber(int $id): void
    {
        $cart = self::raw();
        $cart['numbers'] = array_values(array_filter($cart['numbers'], static fn (int $value): bool => $value !== $id));
        self::save($cart);
    }

    public static function addModem(int $id, int $qty = 1): void
    {
        $modem = Modem::find($id);

        if ($modem === null || $modem['status'] !== 'active' || (int) $modem['stock'] < 1) {
            throw new RuntimeException('این مودم موجود نیست.');
        }

        $cart = self::raw();
        $current = (int) ($cart['modems'][$id] ?? 0);
        $newQty = min(self::MAX_MODEM_QTY, (int) $modem['stock'], $current + max(1, $qty));
        $cart['modems'][$id] = $newQty;
        self::save($cart);
    }

    public static function updateModem(int $id, int $qty): void
    {
        $cart = self::raw();

        if ($qty <= 0) {
            unset($cart['modems'][$id]);
            self::save($cart);

            return;
        }

        $modem = Modem::find($id);

        if ($modem === null) {
            unset($cart['modems'][$id]);
            self::save($cart);

            return;
        }

        $cart['modems'][$id] = min(self::MAX_MODEM_QTY, (int) $modem['stock'], max(1, $qty));
        self::save($cart);
    }

    public static function setActivation(int $operatorId, int $qty): void
    {
        $cart = self::raw();

        if ($qty <= 0) {
            unset($cart['activation'][$operatorId]);
            self::save($cart);

            return;
        }

        $price = ActivationCodePrice::price($operatorId);

        if ($price <= 0) {
            throw new RuntimeException('قیمت کد فعال‌سازی این اپراتور هنوز تنظیم نشده است.');
        }

        $cart['activation'][$operatorId] = min(self::MAX_ACTIVATION_QTY, max(1, $qty));
        self::save($cart);
    }

    public static function removeItem(string $type, int $id): void
    {
        match ($type) {
            'number' => self::removeNumber($id),
            'modem' => self::updateModem($id, 0),
            'activation' => self::setActivation($id, 0),
            default => null,
        };
    }

    public static function clear(): void
    {
        Session::forget(self::KEY);
    }

    /**
     * آیتم‌های قابل نمایش سبد به همراه قیمت‌های جاری.
     *
     * @return array{numbers:array<int,array<string,mixed>>, modems:array<int,array<string,mixed>>, activation:array<int,array<string,mixed>>, total:int, count:int}
     */
    public static function items(): array
    {
        $cart = self::raw();
        $numbers = [];
        $modems = [];
        $activation = [];
        $total = 0;

        foreach ($cart['numbers'] as $id) {
            $row = PhoneNumber::find($id);

            if ($row === null) {
                self::removeNumber($id);
                continue;
            }

            $row['line_total'] = (int) $row['price'];
            $row['available'] = $row['status'] === 'available';
            $total += (int) $row['price'];
            $numbers[] = $row;
        }

        foreach ($cart['modems'] as $id => $qty) {
            $row = Modem::find($id);

            if ($row === null) {
                self::updateModem($id, 0);
                continue;
            }

            $qty = max(1, (int) $qty);
            $row['qty'] = $qty;
            $row['line_total'] = (int) $row['price'] * $qty;
            $row['available'] = $row['status'] === 'active' && (int) $row['stock'] >= $qty;
            $total += (int) $row['line_total'];
            $modems[] = $row;
        }

        foreach ($cart['activation'] as $operatorId => $qty) {
            $operator = null;

            foreach (ActivationCodePrice::all() as $row) {
                if ((int) $row['operator_id'] === (int) $operatorId) {
                    $operator = $row;
                    break;
                }
            }

            if ($operator === null) {
                self::setActivation($operatorId, 0);
                continue;
            }

            $qty = max(1, (int) $qty);
            $price = (int) $operator['price'];
            $operator['qty'] = $qty;
            $operator['line_total'] = $price * $qty;
            $operator['available'] = $price > 0;
            $total += $price * $qty;
            $activation[] = $operator;
        }

        return [
            'numbers' => $numbers,
            'modems' => $modems,
            'activation' => $activation,
            'total' => $total,
            'count' => self::count(),
        ];
    }

    /** @return array<int, string> */
    public static function validate(): array
    {
        if (self::isEmpty()) {
            return ['سبد خرید شما خالی است.'];
        }

        $errors = [];
        $items = self::items();

        foreach ($items['numbers'] as $number) {
            if (!$number['available']) {
                $errors[] = 'شماره ' . fa_num($number['number']) . ' دیگر در دسترس نیست؛ آن را از سبد حذف کنید.';
            }
        }

        foreach ($items['modems'] as $modem) {
            if (!$modem['available']) {
                $errors[] = 'موجودی مودم ' . $modem['brand'] . ' ' . $modem['model'] . ' کافی نیست.';
            }
        }

        foreach ($items['activation'] as $activationCode) {
            if (!$activationCode['available']) {
                $errors[] = 'قیمت کد فعال‌سازی ' . $activationCode['operator_name'] . ' تنظیم نشده است.';
            }
        }

        return $errors;
    }

    public static function type(): string
    {
        $items = self::items();
        $types = [];

        if ($items['numbers'] !== []) {
            $types[] = 'number';
        }

        if ($items['modems'] !== []) {
            $types[] = 'modem';
        }

        if ($items['activation'] !== []) {
            $types[] = 'activation_code';
        }

        return count($types) === 1 ? $types[0] : 'mixed';
    }
}
