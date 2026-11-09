<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\Db;
use App\Support\Jalali;

final class ReportService
{
    /** @return array<string, mixed> */
    public function salesOverview(?string $from = null, ?string $to = null): array
    {
        $from = Jalali::parse($from) ?? date('Y-m-01');
        $to = Jalali::parse($to) ?? date('Y-m-d');
        $paid = Order::PAID_STATUSES;
        $placeholders = implode(',', array_fill(0, count($paid), '?'));
        $params = array_merge($paid, [$from . ' 00:00:00', $to . ' 23:59:59']);

        $totals = Db::one(
            "SELECT COUNT(*) AS orders_count, COALESCE(SUM(total_price), 0) AS revenue
             FROM orders WHERE status IN ({$placeholders}) AND created_at BETWEEN ? AND ?",
            $params
        ) ?? ['orders_count' => 0, 'revenue' => 0];

        $byType = Db::all(
            "SELECT oi.product_type, COUNT(*) AS items, COALESCE(SUM(oi.price * oi.qty), 0) AS total
             FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id
             WHERE o.status IN ({$placeholders}) AND o.created_at BETWEEN ? AND ?
             GROUP BY oi.product_type",
            $params
        );

        $topOperators = Db::all(
            "SELECT JSON_UNQUOTE(JSON_EXTRACT(oi.snapshot, '$.operator_name')) AS operator_name,
                    COUNT(*) AS numbers_count, COALESCE(SUM(oi.price * oi.qty), 0) AS total
             FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id
             WHERE oi.product_type = 'number' AND o.status IN ({$placeholders}) AND o.created_at BETWEEN ? AND ?
             GROUP BY operator_name ORDER BY total DESC LIMIT 5",
            $params
        );

        $daily = Db::all(
            "SELECT DATE(o.created_at) AS day, COUNT(*) AS orders_count, COALESCE(SUM(o.total_price), 0) AS total
             FROM orders o WHERE o.status IN ({$placeholders}) AND o.created_at BETWEEN ? AND ?
             GROUP BY DATE(o.created_at) ORDER BY day ASC",
            $params
        );

        return [
            'from' => $from,
            'to' => $to,
            'orders_count' => (int) $totals['orders_count'],
            'revenue' => (int) $totals['revenue'],
            'by_type' => $byType,
            'top_operators' => $topOperators,
            'daily' => $daily,
        ];
    }

    /** @return array<string, mixed> */
    public function resellerSummary(int $resellerId, ?string $from = null, ?string $to = null): array
    {
        $fromIso = Jalali::parse($from);
        $toIso = Jalali::parse($to);
        $filters = ['from' => $from, 'to' => $to];
        $sales = OrderItem::salesForReseller($resellerId, $filters, 1, 1);
        $profile = User::resellerProfile($resellerId);
        $rate = $profile !== null ? (float) $profile['commission_rate'] : 0.0;

        $numberCount = (int) Db::scalar(
            "SELECT COUNT(*) FROM phone_numbers WHERE reseller_id = ? AND status = 'sold'",
            [$resellerId],
            0
        );

        return [
            'revenue' => $sales['revenue'],
            'items_count' => $sales['total'],
            'numbers_sold' => $numberCount,
            'commission_rate' => $rate,
            'commission' => (int) round($sales['revenue'] * $rate / 100),
            'from' => $fromIso ?? '',
            'to' => $toIso ?? '',
            'profile' => $profile,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, string> $headers key => عنوان
     */
    public function csv(array $rows, array $headers): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, array_values($headers), ',', '"', '\\');

        foreach ($rows as $row) {
            $line = [];

            foreach (array_keys($headers) as $key) {
                $line[] = $row[$key] ?? '';
            }

            fputcsv($handle, $line, ',', '"', '\\');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /** @return array<int, array<string, mixed>> */
    public function orderRowsForExport(array $filters = []): array
    {
        return Order::forExport($filters);
    }
}
