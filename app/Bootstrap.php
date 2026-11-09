<?php

declare(strict_types=1);

namespace App;

use App\Exceptions\HttpException;
use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Support\Cache;
use App\Support\Config;
use App\Support\Db;
use App\Support\Env;
use App\Support\Flash;
use App\Support\Logger;
use App\Support\Session;
use Throwable;

/**
 * راه‌اندازی برنامه: Env → Config → Session → DB → Router
 * و مدیریت سراسری خطاها (در production جزئیات نمایش داده نمی‌شود).
 */
final class Bootstrap
{
    public static function run(): void
    {
        self::requireHelpers();

        try {
            Env::load(BASE_PATH . '/config/env.php');

            if ((string) Env::get('APP_KEY', '') === '') {
                throw new \RuntimeException('APP_KEY در config/env.php تنظیم نشده است.');
            }

            Config::load(require BASE_PATH . '/config/config.php');
            self::configureRuntime();

            set_exception_handler([self::class, 'handleUncaught']);

            Cache::init((string) config('paths.storage') . '/cache');
            Session::start((array) config('session'));
            Flash::boot();
            Db::configure((array) config('db'));
            self::securityHeaders();

            $router = new Router();
            require BASE_PATH . '/config/routes.php';
            $router->dispatch(Request::capture());
        } catch (ValidationException $e) {
            Flash::withErrors($e->errors(), $e->old(), $e->getMessage());
            Response::back('/');
        } catch (HttpException $e) {
            self::renderError($e->getStatusCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::exception($e);
            self::renderError(500, config('app.debug') ? $e->getMessage() : 'خطای داخلی سرور رخ داد. لطفاً بعداً تلاش کنید.');
        }
    }

    public static function handleUncaught(Throwable $e): void
    {
        if ($e instanceof HttpException) {
            self::renderError($e->getStatusCode(), $e->getMessage());

            return;
        }

        Logger::exception($e);
        self::renderError(500, config('app.debug') ? $e->getMessage() : 'خطای داخلی سرور رخ داد.');
    }

    private static function requireHelpers(): void
    {
        static $loaded = false;

        if ($loaded) {
            return;
        }

        require BASE_PATH . '/app/Support/helpers.php';
        require_once BASE_PATH . '/lib/jdf.php';
        $loaded = true;
    }

    private static function configureRuntime(): void
    {
        date_default_timezone_set('Asia/Tehran');
        mb_internal_encoding('UTF-8');

        error_reporting(E_ALL);
        ini_set('display_errors', config('app.debug') ? '1' : '0');
        ini_set('log_errors', '1');

        $logDir = (string) config('paths.storage') . '/logs';

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        ini_set('error_log', $logDir . '/php-error.log');
        ini_set('default_charset', 'UTF-8');
        set_time_limit(30);
    }

    private static function securityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cross-Origin-Opener-Policy: same-origin');

        if (str_starts_with((string) config('app.url'), 'https://')) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    public static function renderError(int $status, string $message): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/html; charset=UTF-8');
        }

        $viewFile = BASE_PATH . '/views/errors/http.php';

        if (!is_file($viewFile)) {
            echo e($message);
            exit;
        }

        $title = match ($status) {
            403 => 'دسترسی غیرمجاز',
            404 => 'صفحه یافت نشد',
            419 => 'اعتبار فرم منقضی شد',
            429 => 'تعداد درخواست بیش از حد',
            default => 'خطای سرور',
        };

        try {
            require $viewFile;
            exit;
        } catch (Throwable) {
            echo e($message);
            exit;
        }
    }
}
