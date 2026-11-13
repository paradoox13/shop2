<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Controller;
use App\Http\Request;
use App\Models\ActivationCodePrice;
use App\Models\ContactMessage;
use App\Models\Modem;
use App\Models\Operator;
use App\Models\PhoneNumber;
use App\Support\Mailer;
use App\Models\Setting;
use App\Support\RateLimiter;
use App\Support\Validator;

final class HomeController extends Controller
{
    public function index(Request $request): string
    {
        $page = max(1, (int) $request->query('page', 1));
        $filters = $request->all();
        unset($filters['page'], $filters['_token']);

        $numbers = PhoneNumber::search($filters, $page, (int) config('pagination.site', 12), null, true);
        $modems = Modem::search(['sort' => 'newest'], 1, 4, null, true);

        return $this->view('pages/home', [
            'title' => setting('site_name', (string) config('app.name')) . ' | ' . setting('site_tagline', 'خرید سیم‌کارت و مودم'),
            'metaDescription' => setting('site_description'),
            'numbers' => $numbers,
            'modems' => $modems,
            'operators' => Operator::all(),
            'activationPrices' => ActivationCodePrice::all(),
            'filters' => $filters,
            'stats' => [
                'available' => PhoneNumber::countAvailable(),
                'operators' => count(Operator::all()),
                'orders' => (int) \App\Support\Db::scalar("SELECT COUNT(*) FROM orders WHERE status IN ('paid','pending_physical_send','sent','completed')", [], 0),
            ],
            'popularPrefixes' => PhoneNumber::popularPrefixes(6),
        ], 'main');
    }

    public function about(): string
    {
        $fallback = '<p>آوال سیم یک وب‌سایت فعال و معتبر در زمینه فروش انواع سیم‌کارت اعتباری و دائمی در اپراتورهای مختلف و همچنین فروش انواع مودم و کارتخوان با قیمت مناسب و کیفیت بالا بوده که همواره توانسته رضایت مشتریان خود را داشته باشد.</p>'
            . '<p>در آوال سیم می‌توانید شماره‌های رند، خانوادگی، فوق رند، سریالی و شماره‌های دارای تاریخ تولد را در اپراتورهای همراه اول، ایرانسل و رایتل بررسی و خریداری کنید. تمام شماره‌ها پیش از انتشار بررسی می‌شوند و وضعیت آن‌ها در لحظه به‌روزرسانی می‌شود.</p>'
            . '<p>هدف ما ارائه قیمت شفاف، تحویل سریع و پشتیبانی واقعی است. برای خرید عمده یا همکاری به‌عنوان نماینده با ما تماس بگیرید.</p>';

        return $this->staticPage('about', 'about_title', 'درباره ما', $fallback);
    }

    public function terms(): string
    {
        return $this->staticPage('terms', 'terms_title', 'قوانین و مقررات');
    }

    public function privacy(): string
    {
        return $this->staticPage('privacy', 'privacy_title', 'حریم خصوصی');
    }

    public function faq(): string
    {
        return $this->staticPage('faq', 'faq_title', 'سوالات متداول');
    }

    public function contact(Request $request): string
    {
        return $this->view('pages/contact', [
            'title' => 'تماس با ما | ' . setting('site_name'),
            'metaDescription' => 'راه‌های ارتباط با پشتیبانی ' . setting('site_name'),
        ], 'main');
    }

    public function contactSubmit(Request $request): string
    {
        $key = 'contact:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, (int) config('security.contact_max_attempts', 3), (int) config('security.contact_decay', 900))) {
            flash('error', 'تعداد پیام‌های ارسالی بیش از حد مجاز است. لطفاً بعداً تلاش کنید.');
            $this->redirect('/contact');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:120',
            'phone' => 'nullable|mobile',
            'email' => 'nullable|email|max:190',
            'subject' => 'required|min:3|max:150',
            'message' => 'required|min:10|max:3000',
        ], [
            'name' => 'نام',
            'phone' => 'شماره تماس',
            'email' => 'ایمیل',
            'subject' => 'موضوع',
            'message' => 'پیام',
        ]);

        if ($validator->fails()) {
            \App\Support\Flash::withErrors($validator->errors(), $request->all());
            $this->redirect('/contact');
        }

        $data = $validator->validated();
        RateLimiter::hit($key, (int) config('security.contact_decay', 900));

        ContactMessage::create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'ip' => $request->ip(),
        ]);

        $adminEmail = (string) (env('ADMIN_EMAIL', '') ?: setting('support_email'));

        if ($adminEmail !== '') {
            Mailer::send(
                $adminEmail,
                'پیام جدید تماس: ' . $data['subject'],
                "نام: {$data['name']}\nشماره: " . ($data['phone'] ?? '-') . "\nایمیل: " . ($data['email'] ?? '-') . "\n\n{$data['message']}"
            );
        }

        flash('success', 'پیام شما ثبت شد. تیم پشتیبانی در اولین فرصت پاسخ می‌دهد.');
        $this->redirect('/contact');

        return '';
    }

    private function staticPage(string $slug, string $titleKey, string $defaultTitle, string $fallback = ''): string
    {
        return $this->view('pages/static', [
            'title' => setting($titleKey, $defaultTitle) . ' | ' . setting('site_name'),
            'metaDescription' => setting('site_description'),
            'pageTitle' => setting($titleKey, $defaultTitle),
            'pageContent' => Setting::get('page_' . $slug, $fallback),
        ], 'main');
    }
}
