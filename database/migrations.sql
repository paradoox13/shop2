-- ============================================================
--  آوال سیم | اسکیمای کامل دیتابیس
--  MariaDB 10.11 / InnoDB / utf8mb4_unicode_ci
--  اجرا: از طریق public_html/run_migration.php یا MySQL CLI
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------- نقش‌ها و مجوزها -------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `name` VARCHAR(50) NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
    `name` VARCHAR(80) NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_name` VARCHAR(50) NOT NULL,
    `permission_name` VARCHAR(80) NOT NULL,
    PRIMARY KEY (`role_name`, `permission_name`),
    KEY `idx_rp_permission` (`permission_name`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_name`) REFERENCES `roles` (`name`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_name`) REFERENCES `permissions` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- کاربران -------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(60) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'customer',
    `full_name` VARCHAR(120) NULL,
    `phone` VARCHAR(20) NULL,
    `email` VARCHAR(190) NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `totp_secret` VARCHAR(64) NULL,
    `last_login_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_username` (`username`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_phone` (`phone`),
    KEY `idx_users_status` (`status`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role`) REFERENCES `roles` (`name`) ON UPDATE CASCADE,
    CONSTRAINT `chk_users_status` CHECK (`status` IN ('active', 'suspended'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- اپراتورها -------------------------
CREATE TABLE IF NOT EXISTS `operators` (
    `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(60) NOT NULL,
    `slug` VARCHAR(60) NOT NULL,
    `prefix_patterns` JSON NOT NULL,
    `sort_order` SMALLINT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_operators_name` (`name`),
    UNIQUE KEY `uq_operators_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- شماره سیم‌کارت‌ها -------------------------
CREATE TABLE IF NOT EXISTS `phone_numbers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `operator_id` SMALLINT UNSIGNED NOT NULL,
    `number` CHAR(11) NOT NULL,
    `price` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'available',
    `reseller_id` BIGINT UNSIGNED NULL,
    `birth_date` DATE NULL,
    `birth_jy` SMALLINT NULL,
    `birth_jm` TINYINT NULL,
    `birth_jd` TINYINT NULL,
    `description` VARCHAR(500) NULL,
    `views` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_phone_number` (`number`),
    KEY `idx_phone_filter` (`operator_id`, `status`, `price`, `birth_date`),
    KEY `idx_phone_status_price` (`status`, `price`),
    KEY `idx_phone_birth` (`birth_date`),
    KEY `idx_phone_birth_j` (`birth_jy`, `birth_jm`, `birth_jd`),
    KEY `idx_phone_created` (`created_at`),
    KEY `idx_phone_reseller` (`reseller_id`, `status`),
    KEY `idx_phone_status_created` (`status`, `created_at`),
    CONSTRAINT `fk_phone_operator` FOREIGN KEY (`operator_id`) REFERENCES `operators` (`id`),
    CONSTRAINT `fk_phone_reseller` FOREIGN KEY (`reseller_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `chk_phone_status` CHECK (`status` IN ('available', 'reserved', 'sold', 'hidden')),
    CONSTRAINT `chk_phone_number` CHECK (CHAR_LENGTH(`number`) = 11 AND `number` LIKE '09%')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- مودم‌ها -------------------------
CREATE TABLE IF NOT EXISTS `modems` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `brand` VARCHAR(60) NOT NULL,
    `model` VARCHAR(80) NOT NULL,
    `price` BIGINT UNSIGNED NOT NULL,
    `stock` INT UNSIGNED NOT NULL DEFAULT 0,
    `image_path` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `reseller_id` BIGINT UNSIGNED NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_modem_brand_model_reseller` (`brand`, `model`, `reseller_id`),
    KEY `idx_modem_reseller` (`reseller_id`, `status`),
    KEY `idx_modem_status_stock` (`status`, `stock`),
    CONSTRAINT `fk_modem_reseller` FOREIGN KEY (`reseller_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `chk_modem_status` CHECK (`status` IN ('active', 'inactive'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- قیمت کد فعال‌سازی -------------------------
CREATE TABLE IF NOT EXISTS `activation_code_prices` (
    `operator_id` SMALLINT UNSIGNED NOT NULL,
    `price` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`operator_id`),
    CONSTRAINT `fk_acp_operator` FOREIGN KEY (`operator_id`) REFERENCES `operators` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- سفارش‌ها -------------------------
CREATE TABLE IF NOT EXISTS `orders` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `customer_name` VARCHAR(120) NOT NULL,
    `customer_phone` VARCHAR(20) NOT NULL,
    `customer_email` VARCHAR(190) NULL,
    `type` VARCHAR(30) NOT NULL DEFAULT 'mixed',
    `total_price` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
    `tracking_code` VARCHAR(32) NOT NULL,
    `admin_note` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_orders_tracking` (`tracking_code`),
    KEY `idx_orders_status_created` (`status`, `created_at`),
    KEY `idx_orders_phone` (`customer_phone`),
    KEY `idx_orders_user` (`user_id`),
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `chk_orders_status` CHECK (`status` IN ('pending', 'awaiting_payment', 'paid', 'pending_physical_send', 'sent', 'completed', 'canceled', 'failed', 'refunded')),
    CONSTRAINT `chk_orders_type` CHECK (`type` IN ('number', 'modem', 'activation_code', 'mixed'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `product_type` VARCHAR(30) NOT NULL,
    `product_id` BIGINT UNSIGNED NULL,
    `snapshot` JSON NOT NULL,
    `price` BIGINT UNSIGNED NOT NULL,
    `qty` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_items_order` (`order_id`),
    KEY `idx_items_product` (`product_type`, `product_id`),
    CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_items_type` CHECK (`product_type` IN ('number', 'modem', 'activation_code'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- پرداخت‌ها -------------------------
CREATE TABLE IF NOT EXISTS `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `gateway` VARCHAR(30) NOT NULL,
    `authority` VARCHAR(191) NULL,
    `ref_id` VARCHAR(100) NULL,
    `amount` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `card_pan` VARCHAR(30) NULL,
    `error_message` VARCHAR(500) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `paid_at` DATETIME NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payments_authority` (`authority`),
    KEY `idx_payments_status_created` (`status`, `created_at`),
    KEY `idx_payments_order` (`order_id`),
    CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_payments_status` CHECK (`status` IN ('pending', 'paid', 'failed', 'canceled', 'refunded'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- پروفایل نمایندگان -------------------------
CREATE TABLE IF NOT EXISTS `reseller_profiles` (
    `user_id` BIGINT UNSIGNED NOT NULL,
    `display_name` VARCHAR(120) NOT NULL,
    `contact` VARCHAR(150) NULL,
    `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_reseller_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_reseller_commission` CHECK (`commission_rate` >= 0 AND `commission_rate` <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- ایمپورت‌ها -------------------------
CREATE TABLE IF NOT EXISTS `import_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reseller_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(20) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `total_rows` INT UNSIGNED NOT NULL DEFAULT 0,
    `processed_rows` INT UNSIGNED NOT NULL DEFAULT 0,
    `inserted_rows` INT UNSIGNED NOT NULL DEFAULT 0,
    `failed_rows` INT UNSIGNED NOT NULL DEFAULT 0,
    `cursor_offset` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `headers` JSON NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `error_log` MEDIUMTEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_import_reseller` (`reseller_id`, `created_at`),
    KEY `idx_import_status` (`status`, `updated_at`),
    CONSTRAINT `fk_import_reseller` FOREIGN KEY (`reseller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_import_status` CHECK (`status` IN ('pending', 'processing', 'completed', 'failed')),
    CONSTRAINT `chk_import_type` CHECK (`type` IN ('number', 'modem'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- پیامک‌ها -------------------------
CREATE TABLE IF NOT EXISTS `sms_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `phone` VARCHAR(20) NOT NULL,
    `template` VARCHAR(60) NOT NULL,
    `message` TEXT NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'queued',
    `provider` VARCHAR(30) NULL,
    `provider_response` TEXT NULL,
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `order_id` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `sent_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sms_status` (`status`, `created_at`),
    KEY `idx_sms_phone` (`phone`),
    KEY `idx_sms_order` (`order_id`),
    CONSTRAINT `fk_sms_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
    CONSTRAINT `chk_sms_status` CHECK (`status` IN ('queued', 'sent', 'failed'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- پیام‌های تماس -------------------------
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    `phone` VARCHAR(20) NULL,
    `email` VARCHAR(190) NULL,
    `subject` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `ip` VARCHAR(45) NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'new',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_contact_status` (`status`, `created_at`),
    CONSTRAINT `chk_contact_status` CHECK (`status` IN ('new', 'read'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- تنظیمات -------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `key` VARCHAR(80) NOT NULL,
    `value` MEDIUMTEXT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- لاگ عملیات حساس -------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(80) NOT NULL,
    `entity` VARCHAR(60) NULL,
    `entity_id` VARCHAR(40) NULL,
    `meta` JSON NULL,
    `ip` VARCHAR(45) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_user` (`user_id`, `created_at`),
    KEY `idx_audit_action` (`action`, `created_at`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- صف کارها (Cron) -------------------------
CREATE TABLE IF NOT EXISTS `jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` VARCHAR(40) NOT NULL,
    `payload` JSON NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `available_at` DATETIME NOT NULL,
    `last_error` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `processed_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_jobs_status_available` (`status`, `available_at`),
    KEY `idx_jobs_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- رزرو شماره -------------------------
CREATE TABLE IF NOT EXISTS `reservations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `phone_number_id` BIGINT UNSIGNED NOT NULL,
    `order_id` BIGINT UNSIGNED NULL,
    `token` VARCHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_reservations_token` (`token`),
    KEY `idx_reservations_expires` (`expires_at`),
    KEY `idx_reservations_number` (`phone_number_id`),
    KEY `idx_reservations_order` (`order_id`),
    CONSTRAINT `fk_reservations_number` FOREIGN KEY (`phone_number_id`) REFERENCES `phone_numbers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reservations_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- تلاش‌های ورود -------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip` VARCHAR(45) NOT NULL,
    `username` VARCHAR(60) NULL,
    `success` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_attempts_ip` (`ip`, `created_at`),
    KEY `idx_attempts_username` (`username`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
