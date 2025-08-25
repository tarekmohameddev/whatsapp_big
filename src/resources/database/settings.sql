-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 05, 2025 at 06:13 AM
-- Server version: 8.0.42-0ubuntu0.24.04.1
-- PHP Version: 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `xsender`
--

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint UNSIGNED NOT NULL,
  `uid` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `channel` enum('email','sms','whatsapp') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `uid`, `channel`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, '2i9vRUup-TFc8TIfIEAfdfx-H2pSRlwe', NULL, 'email', 'xsender@admin.com', NULL, NULL),
(2, '07SEzKCB-19Y6NYHb4kFAax-yH4a9xH7', NULL, 'phone', '###', NULL, NULL),
(3, '1JIVxzBR-xNxGFdDcEF3Dys-zUTfe6vM', NULL, 'plugin', '0', NULL, NULL),
(4, '5S2ceHwj-zt1nBgGzso4uC5-5fak8v1v', NULL, 'captcha', '0', NULL, NULL),
(5, '0Q4lNyJ4-jDNYDq3EmgC2fz-fHcODpLj', NULL, 'address', '###', NULL, NULL),
(6, '2ISm46ld-ilQDOLhVQ22Kxf-y1FeHITb', NULL, 'google_map_iframe', '###', NULL, NULL),
(7, '3Y2Y6mK6-DZgmIlbDI5SN54-t6w1j8if', NULL, 'site_name', 'xsender', NULL, NULL),
(8, 'TU19VAoq-9ywdpPbS1Br5py-lDokRcS4', NULL, 'time_zone', 'UTC', NULL, NULL),
(9, '5oMdA1hz-fNBaGxVvNbuRhY-alJAzofL', NULL, 'app_version', '3.31', NULL, NULL),
(10, '80ILoo1C-peHOuKKGVbeFWX-Yu1ZyWFR', NULL, 'country_code', '1', NULL, NULL),
(11, '8SpImyzM-PJ8bHdaf87LRdR-d45j1kle', NULL, 'currency_name', 'USD', NULL, NULL),
(12, 'yA77QbrV-JWKg0I9g99rlcI-2VfxA2J2', NULL, 'currency_symbol', '$', NULL, NULL),
(13, '9BOQs1YV-LJJh09u19vC8wY-cQYTqkLC', NULL, 'webhook_verify_token', 'xsender', NULL, NULL),
(14, '9Q4ZHCtJ-ak9IFKWTJfCbgC-8IkKMA8S', NULL, 'api_sms_method', '1', NULL, NULL),
(15, 'LDymOuYj-sJiePTfDDSL5x1-q1T9uYi8', NULL, 'app_link', '###', NULL, NULL),
(16, 'B3HySK4u-W9FsZXEPROXR4P-83zvyIs1', NULL, 'theme_dir', '0', NULL, NULL),
(17, 'f6LDIQRy-wU85x8027kXMqJ-oPCps7M3', NULL, 'theme_mode', '0', NULL, NULL),
(18, 'ZlflSchl-nDn4Beb3UdOIth-ZXXK4yn1', NULL, 'social_login', '0', NULL, NULL),
(19, 'nvMviHvr-1MTZawVKu2noBQ-RSMjny28', NULL, 'social_login_with', '{\"google_oauth\":{\"status\":\"1\",\"client_id\":\"580301070453-job03fms4l7hrlnobt7nr5lbsk9bvoq9.apps.googleusercontent.com\",\"client_secret\":\"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\"}}', NULL, NULL),
(20, 'KsKMEAwo-5fZLdYbOaqurba-vcPszah8', NULL, 'available_plugins', '{\"beefree\":{\"status\":\"0\",\"client_id\":\"b2369021-3e95-4ca4-a8c8-2ed3e2531865\",\"client_secret\":\"uL3UKV8V4RLv77vodnNTM8e93np9OYsS5P2mJ0373Nt9ghbwoRbn\"}}', NULL, NULL),
(21, 'F0ddXBBR-NnJUvTwEY9ZIcE-FtsAbjr6', NULL, 'member_authentication', '{\"registration\":\"1\",\"login\":\"1\"}', NULL, NULL),
(22, 'CyyjTztG-0NEypVtKz4gVHr-VdbtKiD7', NULL, 'google_recaptcha', '{\"status\":\"0\",\"key\":\"6Lc5PpImAAAAABM-m4EgWw8vGEb7Tqq5bMOSI1Ot\",\"secret_key\":\"6Lc5PpImAAAAACdUh5Hth8NXRluA04C-kt4Xdbw7\"}', NULL, NULL),
(23, 'tPtyj46p-rEVDG6SQVjpIdD-THp8XIB9', NULL, 'captcha_with_login', '0', NULL, NULL),
(24, '5am4Udgf-tbRDCDXliIRLt2-15fwPTTJ', NULL, 'captcha_with_registration', '0', NULL, NULL),
(25, 'jov9w9gx-wLsoP0o1gbOZCw-f9ZXO7P1', NULL, 'registration_otp_verification', '1', NULL, NULL),
(26, '25GFtRQk-r2Lygyi1KbIVAU-NNvj3sFV', NULL, 'email_otp_verification', '1', NULL, NULL),
(27, 'u5iULkiD-Z5uYLNKb33PtJE-JWDGAO81', NULL, 'otp_expired_status', '0', NULL, NULL),
(28, '0TZdemxU-uWBxomESYlHHtD-7D49B1uF', NULL, 'email_notifications', '1', NULL, NULL),
(29, 'gtOviiOM-a0MdJLsjyyjhzJ-tgJRrZy4', NULL, 'default_email_template', 'hi, {{message}}', NULL, NULL),
(30, '641K90D5-p8r6u7M8ZAKU8j-FBkKQS2G', NULL, 'contact_meta_data', '{\"date_of_birth\":{\"status\":\"1\",\"type\":1}}', NULL, NULL),
(31, '6GPuPKmn-852XYjHPETwj87-9AqYPwd6', NULL, 'last_cron_run', '2025-06-05 06:06:55', NULL, NULL),
(32, '0D12NLMW-edGcHgTvapMqg4-QAq94LD8', NULL, 'onboarding_bonus', '0', NULL, NULL),
(33, 'motsLy1R-Bj49u7DDOUtGIJ-3O0heiO9', NULL, 'onboarding_bonus_plan', NULL, NULL, NULL),
(34, 'FehYkovx-MOrzfJWB7Mu6Jd-DGIexPm2', NULL, 'debug_mode', '0', NULL, NULL),
(35, '9GZt9m1e-g0aVB3FExk9LpZ-oXaxpfPD', NULL, 'maintenance_mode', '0', NULL, NULL),
(36, '6g8LNuZk-KGcW9jmoTQi8Qs-V7OFpbr0', NULL, 'maintenance_mode_message', '<p>Please be advised that there will be scheduled downtime across our network from 12.00AM to 2.00AM</p>', NULL, NULL),
(37, '8JTY2IYi-iM9SEkPqqfyMMw-JESqJBKc', NULL, 'landing_page', '1', NULL, NULL),
(38, 'RcKND4G9-r9XFCNL8bCYvqE-kxzitTo9', NULL, 'whatsapp_word_count', '320', NULL, NULL),
(39, 'rrXzM8Rv-dlp6S6JOqVaTf2-HsmvR1t6', NULL, 'sms_word_count', '320', NULL, NULL),
(40, '16l51gOR-yaAmjyW4Rco4HH-lC9rsX4B', NULL, 'sms_word_unicode_count', '320', NULL, NULL),
(41, '3PsaKFVq-SKtM3v0zdcYAsh-yYcHKsfC', NULL, 'primary_color', '#f25d6d', NULL, NULL),
(42, '7FMdu4r8-e0vBXUKfQUm7Gj-jib44faT', NULL, 'secondary_color', '#f64b4d', NULL, NULL),
(43, '6At6ADKH-rKABlfeKArmBCI-wuxaaWI1', NULL, 'trinary_color', '#ffa360', NULL, NULL),
(44, '0pDzZWq9-dHFbT0tL2kVC0w-MVdS2rt8', NULL, 'primary_text_color', '#ffffff', NULL, NULL),
(45, 'AFVA1DCe-hwCEUgTz168AQ1-Xn9zrKN6', NULL, 'copyright', 'iGen Solutions Ltd', NULL, NULL),
(46, '4LGR0hYl-s0fI7B7YmdY37O-wr5esOXJ', NULL, 'mime_types', '[\"png\",\"jpg\",\"jpeg\",\"jpeg\",\"jpg\",\"png\",\"webp\"]', NULL, NULL),
(47, 'nINDMhVR-Xxn4vg7RtWcHaO-FyrwGVe0', NULL, 'max_file_size', '20000', NULL, NULL),
(48, 'Oh4ykjgV-v63x4G04fBROZc-XxDBGv75', NULL, 'max_file_upload', '4', NULL, NULL),
(49, '7Cat6DsI-pHjxMx8KZDMIko-0PhuT3d0', NULL, 'currencies', '{\"USD\":{\"name\":\"United States Dollar\",\"symbol\":\"$\",\"rate\":\"1\",\"status\":\"1\",\"is_default\":\"1\"},\"BDT\":{\"name\":\"Bangladeshi Taka\",\"symbol\":\"\\u09f3\",\"rate\":\"114\",\"status\":\"0\",\"is_default\":\"0\"}}', NULL, NULL),
(50, 'tVH1oy4A-2SKe0oQHwMLEGL-JFY9VuA9', NULL, 'paginate_number', '7', NULL, NULL),
(51, '5XGi2LpQ-ByLSmt8YCMMBMi-hYLJeddH', NULL, 'auth_heading', 'Start turning your ideas into reality.', NULL, NULL),
(52, '9qRbPRUu-44vEfPuNoaj8rF-JXzq9wc9', NULL, 'authentication_background', '6841351568c071749103893.webp', NULL, NULL),
(53, 'kzgk4nz7-yiEI106lEVOLFu-oql4YDe1', NULL, 'authentication_background_inner_image_one', '684135159fb1e1749103893.webp', NULL, NULL),
(54, 'yFoqCWHT-GAyyNSbMd4zHz8-PGnMUnR5', NULL, 'authentication_background_inner_image_two', '68413515aafa81749103893.webp', NULL, NULL),
(55, '5IQL9rVS-g18vc3y1LWpi5L-OfGC848M', NULL, 'meta_title', 'Welcome To Xsender', NULL, NULL),
(56, 'r9Ex7mvB-OAzCNYZ5PvHIWN-ljd9KW15', NULL, 'meta_description', 'Start your marketing journey today', NULL, NULL),
(57, '9d6F0gWB-qg5rxPNRMxwtXg-TGcHSQ1q', NULL, 'meta_keywords', '[\"bulk\",\"sms\",\"email\",\"whatsapp\",\"marketing\"]', NULL, NULL),
(58, '4VStOEYD-mwclBhPZEuIc4X-kiDniUrO', NULL, 'site_logo', '66e9dd6484e241726602596.webp', NULL, NULL),
(59, 'oIk9jPVw-L7VyzHpqM3YbAY-c7No9686', NULL, 'site_square_logo', '684134dcb48001749103836.webp', NULL, NULL),
(60, '8TszJCE9-NPOtwNSJd5tnec-Fh4DbcFl', NULL, 'panel_logo', '66e9dd64e9c721726602596.webp', NULL, NULL),
(61, '1K2qBHmO-YvRAS3098d9tq1-QZXtbZgM', NULL, 'panel_square_logo', '684134dcbc51f1749103836.webp', NULL, NULL),
(62, '2l9bVEyW-PMQBlHYVffMvY9-cKg9fUos', NULL, 'favicon', '66e9dd65033111726602597.webp', NULL, NULL),
(63, '1PoOS960-S9gX9WYxqHRcXq-fi0UUoMd', NULL, 'meta_image', '66e9dd65076b11726602597.webp', NULL, NULL),
(64, NULL, NULL, 'system_installed_at', '2025-06-05 06:06:59', NULL, NULL),
(65, NULL, NULL, 'is_domain_verified', '1', NULL, NULL),
(66, NULL, NULL, 'next_verification', '2025-06-06 06:06:59', NULL, NULL),
(67, NULL, NULL, 'store_as_webp', '0', NULL, NULL),
(68, NULL, NULL, 'filter_duplicate_contact', '0', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`),
  ADD KEY `settings_uid_index` (`uid`),
  ADD KEY `settings_channel_index` (`channel`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
