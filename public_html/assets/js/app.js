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
        try {
            if (isDark()) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
            syncThemeButtons();
        } catch (e) {
            // localStorage ممکن است در حالت خصوصی در دسترس نباشد
        }
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
