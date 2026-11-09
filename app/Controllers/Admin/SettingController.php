<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Http\Controller;
use App\Http\Request;
use App\Models\Setting;
use App\Models\User;
use App\Services\BackupService;
use App\Services\Gateways\GatewayFactory;
use App\Support\Audit;
use App\Support\Auth;
use App\Support\Session;
use App\Support\Totp;
use App\Support\Validator;

final class SettingController extends Controller
{
    public function index(): string
    {
        $this->requirePermission('settings.manage');

        return $this->view('admin/settings/index', [
            'title' => 'تنظیمات',
            'settings' => Setting::all(),
            'gateways' => GatewayFactory::options(),
            'activeGateway' => GatewayFactory::activeName(),
            'smsProviders' => ['kavenegar' => 'کاوه‌نگار', 'melipayamak' => 'ملی‌پیامک'],
            'backups' => (new BackupService())->listBackups(),
            'twoFactorEnabled' => !empty(Auth::user()['totp_secret']),
        ], 'admin');
    }

    public function update(Request $request): string
    {
        $this->requirePermission('settings.manage');

        $data = $this->validate($request, [
            'site_name' => 'required|min:2|max:80',
            'site_tagline' => 'required|min:2|max:160',
            'site_description' => 'nullable|max:300',
            'support_phone' => 'nullable|max:30',
            'support_email' => 'nullable|email|max:190',
            'address' => 'nullable|max:300',
            'instagram' => 'nullable|max:190',
            'telegram' => 'nullable|max:190',
            'whatsapp' => 'nullable|max:190',
            'footer_about' => 'nullable|max:1000',
            'notify_phone' => 'nullable|mobile',
            'order_note' => 'nullable|max:1000',
            'commission_default' => 'nullable|numeric|between:0,100',
            'payment_driver' => 'nullable|in:,zarinpal,idpay,nextpay',
            'sms_provider' => 'nullable|in:,kavenegar,melipayamak',
            'about_title' => 'nullable|max:150',
            'page_about' => 'nullable|max:20000',
            'terms_title' => 'nullable|max:150',
            'page_terms' => 'nullable|max:20000',
            'privacy_title' => 'nullable|max:150',
            'page_privacy' => 'nullable|max:20000',
            'faq_title' => 'nullable|max:150',
            'page_faq' => 'nullable|max:20000',
        ], [
            'site_name' => 'نام سایت',
            'site_tagline' => 'شعار',
            'support_email' => 'ایمیل پشتیبانی',
            'notify_phone' => 'موبایل اعلان',
            'payment_driver' => 'درگاه پرداخت',
            'sms_provider' => 'سرویس پیامک',
        ]);

        $values = [];
        foreach ($data as $key => $value) {
            $values[$key] = is_string($value) ? $value : (string) $value;
        }

        Setting::setMany($values);
        User::flushResellerCache();
        Audit::log('settings.updated', 'settings', null, ['keys' => array_keys($values)]);
        flash('success', 'تنظیمات ذخیره شد.');
        $this->redirect('/admin/settings');

        return '';
    }

    public function twoFactor(): string
    {
        $this->requirePermission('settings.manage');
        $user = Auth::user();
        $secret = Session::get('2fa_pending_secret');

        if (!is_string($secret) || $secret === '') {
            $secret = Totp::generateSecret();
            Session::put('2fa_pending_secret', $secret);
        }

        return $this->view('admin/settings/two-factor', [
            'title' => 'تأیید دو مرحله‌ای',
            'secret' => $secret,
            'enabled' => !empty($user['totp_secret']),
            'uri' => Totp::provisioningUri((string) setting('site_name', 'AvalSim'), (string) $user['username'], $secret),
        ], 'admin');
    }

    public function twoFactorAction(Request $request): string
    {
        $this->requirePermission('settings.manage');
        $user = Auth::user();
        $action = $request->string('action');

        if ($user === null) {
            $this->redirect('/admin/login');
        }

        if ($action === 'disable') {
            $code = $request->string('code');
            $secret = (string) ($user['totp_secret'] ?? '');

            if ($secret === '' || !Totp::verify($secret, $code)) {
                flash('error', 'کد تأیید نادرست است.');
                $this->redirect('/admin/settings/2fa');
            }

            User::setTotpSecret((int) $user['id'], null);
            Auth::refresh();
            Audit::log('2fa.disabled', 'users', (string) $user['id']);
            flash('success', 'تأیید دو مرحله‌ای غیرفعال شد.');
            $this->redirect('/admin/settings');
        }

        $secret = (string) Session::get('2fa_pending_secret', '');
        $code = $request->string('code');

        if ($secret === '' || !Totp::verify($secret, $code)) {
            flash('error', 'کد تأیید نادرست است. کد ۶ رقمی برنامه Authenticator را وارد کنید.');
            $this->redirect('/admin/settings/2fa');
        }

        User::setTotpSecret((int) $user['id'], $secret);
        Session::forget('2fa_pending_secret');
        Auth::refresh();
        Audit::log('2fa.enabled', 'users', (string) $user['id']);
        flash('success', 'تأیید دو مرحله‌ای فعال شد.');
        $this->redirect('/admin/settings');

        return '';
    }
}
