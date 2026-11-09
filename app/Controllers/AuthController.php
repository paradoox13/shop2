<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Controller;
use App\Http\Request;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Support\Auth;
use App\Support\RateLimiter;
use App\Support\Session;
use App\Support\Str;
use App\Support\Totp;
use App\Support\Validator;

final class AuthController extends Controller
{
    public function showLogin(Request $request): string
    {
        return $this->loginView('ورود به حساب کاربری', '/login', 'حساب کاربری', (string) $request->query('next', ''));
    }

    public function showResellerLogin(): string
    {
        return $this->loginView('ورود نمایندگان', '/reseller/login', 'پنل نمایندگان', '');
    }

    public function showAdminLogin(): string
    {
        return $this->loginView('ورود مدیران', '/admin/login', 'پنل مدیریت', '');
    }

    public function login(Request $request): string
    {
        return $this->authenticate($request, ['customer', 'reseller', 'admin', 'super_admin'], '/', 'ورود به حساب');
    }

    public function loginReseller(Request $request): string
    {
        return $this->authenticate($request, ['reseller'], '/reseller', 'ورود نماینده');
    }

    public function loginAdmin(Request $request): string
    {
        return $this->authenticate($request, ['admin', 'super_admin'], '/admin', 'ورود مدیر');
    }

    public function showTwoFactor(): string
    {
        if (Session::get('2fa_user_id') === null) {
            $this->redirect('/login');
        }

        return $this->view('auth/two-factor', [
            'title' => 'تأیید دو مرحله‌ای',
        ], 'auth');
    }

    public function verifyTwoFactor(Request $request): string
    {
        $userId = (int) Session::get('2fa_user_id', 0);

        if ($userId <= 0) {
            $this->redirect('/login');
        }

        $key = '2fa:' . $request->ip() . ':' . $userId;

        if (RateLimiter::tooManyAttempts($key, 5, 600)) {
            flash('error', 'تعداد تلاش‌ها بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.');
            $this->redirect('/login/2fa');
        }

        $code = Str::toEnglishDigits($request->string('code'));
        $user = User::find($userId);

        if ($user === null || empty($user['totp_secret']) || !Totp::verify((string) $user['totp_secret'], $code)) {
            RateLimiter::hit($key, 600);
            LoginAttempt::record($request->ip(), $user['username'] ?? null, false);
            flash('error', 'کد تأیید نادرست است.');
            $this->redirect('/login/2fa');
        }

        RateLimiter::clear($key);
        LoginAttempt::record($request->ip(), (string) $user['username'], true);

        if (!Auth::loginByPendingTwoFactor()) {
            flash('error', 'نشست تأیید منقضی شده است؛ دوباره وارد شوید.');
            $this->redirect('/login');
        }

        flash('success', 'خوش آمدید.');
        $this->redirect(Auth::homeUrl());

        return '';
    }

    public function showRegister(): string
    {
        return $this->view('auth/register', [
            'title' => 'ثبت‌نام | ' . setting('site_name'),
        ], 'auth');
    }

    public function register(Request $request): string
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|min:3|max:30|regex:/^[a-zA-Z0-9_]+$/|unique:users,username',
            'password' => 'required|min:8|max:72|confirmed',
            'full_name' => 'required|min:3|max:120',
            'phone' => 'required|mobile|unique:users,phone',
            'email' => 'nullable|email|max:190',
        ], [
            'username' => 'نام کاربری',
            'password' => 'رمز عبور',
            'full_name' => 'نام و نام خانوادگی',
            'phone' => 'شماره موبایل',
            'email' => 'ایمیل',
        ]);

        if ($validator->fails()) {
            \App\Support\Flash::withErrors($validator->errors(), $request->all());
            $this->redirect('/register');
        }

        $data = $validator->validated();
        $phone = Str::normalizeMobile((string) $data['phone']);

        $userId = User::create((string) $data['username'], (string) $data['password'], 'customer', [
            'full_name' => (string) $data['full_name'],
            'phone' => $phone,
            'email' => $data['email'] ?: null,
        ]);

        $user = User::find($userId);

        if ($user === null) {
            flash('error', 'ثبت‌نام انجام شد اما ورود خودکار ممکن نشد.');
            $this->redirect('/login');
        }

        Auth::login($user);
        flash('success', 'حساب شما ساخته شد. خوش آمدید!');
        $this->redirect('/');

        return '';
    }

    public function logout(Request $request): string
    {
        Auth::logout();
        flash('success', 'از حساب خود خارج شدید.');
        $this->redirect('/');

        return '';
    }

    private function authenticate(Request $request, array $allowedRoles, string $fallback, string $label): string
    {
        $username = trim(Str::toEnglishDigits($request->string('username')));
        $password = $request->string('password');
        $ip = $request->ip();
        $key = 'login:' . $ip . ':' . $username;

        if (RateLimiter::tooManyAttempts($key, (int) config('security.login_max_attempts', 5), (int) config('security.login_decay', 600))) {
            LoginAttempt::record($ip, $username, false);
            flash('error', 'تلاش‌های ورود بیش از حد مجاز است. ۱۰ دقیقه دیگر دوباره امتحان کنید.');
            $this->redirect($request->path());
        }

        if ($username === '' || $password === '') {
            flash('error', 'نام کاربری و رمز عبور الزامی است.');
            $this->redirect($request->path());
        }

        $result = Auth::attempt($username, $password);

        if ($result['status'] === '2fa') {
            RateLimiter::clear($key);
            LoginAttempt::record($ip, $username, true);
            $this->redirect('/login/2fa');
        }

        if ($result['status'] !== 'ok') {
            RateLimiter::hit($key, (int) config('security.login_decay', 600));
            LoginAttempt::record($ip, $username, false);
            flash('error', $result['status'] === 'suspended' ? 'حساب شما غیرفعال است. با پشتیبانی تماس بگیرید.' : 'نام کاربری یا رمز عبور نادرست است.');
            $this->redirect($request->path());
        }

        RateLimiter::clear($key);
        LoginAttempt::record($ip, $username, true);

        $role = (string) ($result['user']['role'] ?? 'customer');

        if (!in_array($role, $allowedRoles, true)) {
            Auth::logout();
            flash('error', $role === 'reseller' ? 'برای ورود نمایندگان از صفحه ورود نمایندگان استفاده کنید.' : 'دسترسی به این بخش برای نقش شما مجاز نیست.');
            $this->redirect($request->path());
        }

        if ($role === 'reseller' && !in_array('reseller', $allowedRoles, true)) {
            Auth::logout();
            flash('error', 'نمایندگان باید از پنل نمایندگان وارد شوند.');
            $this->redirect($request->path());
        }

        $next = (string) $request->input('next', '');
        $redirect = $next !== '' && str_starts_with($next, '/') && !str_starts_with($next, '//')
            ? $next
            : ($role === 'customer' ? $fallback : Auth::homeUrl());

        flash('success', 'خوش آمدید ' . ($result['user']['full_name'] ?? $username) . '!');
        $this->redirect($redirect);

        return '';
    }

    private function loginView(string $title, string $action, string $heading, string $next): string
    {
        return $this->view('auth/login', [
            'title' => $title . ' | ' . setting('site_name'),
            'action' => $action,
            'heading' => $heading,
            'next' => $next,
            'registerLink' => $action === '/login',
        ], 'auth');
    }
}
