/* آوال سیم — اسکریپت‌های عمومی */
(function () {
    'use strict';

    function isDark() {
        return document.documentElement.classList.contains('dark');
    }

    function syncThemeButtons() {
        document.querySelectorAll('[data-theme-icon]').forEach(function (el) {
            el.textContent = isDark() ? '☀️' : '🌙';
        });
    }

    window.toggleTheme = function () {
        var html = document.documentElement;
        html.classList.toggle('dark');
        var dark = html.classList.contains('dark');
        html.style.colorScheme = dark ? 'dark' : 'light';

        try {
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        } catch (e) {
            // localStorage ممکن است در حالت خصوصی در دسترس نباشد
        }

        syncThemeButtons();
    };

    window.copyText = function (text, button) {
        var done = function () {
            if (!button) return;
            var original = button.getAttribute('data-label') || button.textContent;
            button.setAttribute('data-label', original);
            button.textContent = 'کپی شد ✓';
            setTimeout(function () {
                button.textContent = original;
            }, 1600);
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done).catch(function () {
                window.prompt('برای کپی دستی:', text);
            });
        } else {
            window.prompt('برای کپی دستی:', text);
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.documentElement.style.colorScheme = isDark() ? 'dark' : 'light';
        syncThemeButtons();

        document.querySelectorAll('.js-auto-hide').forEach(function (el) {
            setTimeout(function () {
                el.style.transition = 'opacity .4s ease';
                el.style.opacity = '0';
                setTimeout(function () { el.remove(); }, 450);
            }, 6000);
        });
    });
})();
