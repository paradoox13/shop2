<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Support\Auth;
use App\Support\Logger;
use App\Support\Session;
use RuntimeException;
use Throwable;

final class PaymentController extends Controller
{
    public function start(Request $request): string
    {
        $tracking = $request->string('tracking_code');
        $order = Order::findByTracking($tracking);

        if ($order === null || !$this->hasAccess($order)) {
            flash('error', 'سفارش مورد نظر یافت نشد.');
            $this->redirect('/');
        }

        if (in_array((string) $order['status'], Order::PAID_STATUSES, true)) {
            flash('success', 'این سفارش قبلاً پرداخت شده است.');
            $this->redirect('/order/' . $tracking);
        }

        try {
            if (in_array((string) $order['status'], ['failed', 'pending', 'canceled'], true)) {
                $freshOrder = (new OrderService())->prepareRetry((int) $order['id']);
            } else {
                $freshOrder = $order;
            }

            $payment = (new PaymentService())->initiate((int) $freshOrder['id']);
            Response::redirectExternal($payment['redirect']);
        } catch (Throwable $e) {
            Logger::warning('Payment retry failed', ['tracking' => $tracking, 'error' => $e->getMessage()]);
            flash('error', $e->getMessage());
            $this->redirect('/order/' . $tracking);
        }

        return '';
    }

    public function callback(string $gateway): string
    {
        $payload = array_merge($_GET, $_POST);
        unset($payload['_token']);

        try {
            $outcome = (new PaymentService())->handleCallback($gateway, $payload);
        } catch (Throwable $e) {
            Logger::exception($e, 'Payment callback error');
            flash('error', 'پردازش نتیجه پرداخت با خطا مواجه شد. اگر مبلغ کسر شده، تا ۱۰ دقیقه آینده سفارش تکمیل می‌شود.');
            $this->redirect('/');
        }

        $order = $outcome['order'] ?? null;

        if (!is_array($order)) {
            flash('error', $outcome['message']);
            $this->redirect('/');
        }

        $tracking = (string) $order['tracking_code'];
        $this->grantAccess($tracking);

        $type = match ($outcome['status']) {
            'paid' => 'success',
            'pending' => 'info',
            default => 'error',
        };

        flash($type, $outcome['message']);
        $this->redirect('/order/' . $tracking);

        return '';
    }

    /** @param array<string, mixed> $order */
    private function hasAccess(array $order): bool
    {
        $tracking = (string) ($order['tracking_code'] ?? '');

        if (Auth::isStaff()) {
            return true;
        }

        if (Auth::check() && (int) ($order['user_id'] ?? 0) === Auth::id()) {
            return true;
        }

        $access = Session::get('order_access', []);

        return is_array($access) && in_array($tracking, $access, true);
    }

    private function grantAccess(string $tracking): void
    {
        $access = Session::get('order_access', []);
        $access = is_array($access) ? $access : [];
        $access[] = $tracking;
        Session::put('order_access', array_values(array_unique($access)));
    }
}
