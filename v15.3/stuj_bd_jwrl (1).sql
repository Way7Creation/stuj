-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Июл 06 2025 г., 19:30
-- Версия сервера: 8.0.42-0ubuntu0.22.04.1
-- Версия PHP: 8.1.2-1ubuntu2.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `stuj_bd_jwrl`
--

-- --------------------------------------------------------

--
-- Структура таблицы `attributes`
--

CREATE TABLE `attributes` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `attributes`
--

INSERT INTO `attributes` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'тест', '2025-07-04 19:33:22', '2025-07-04 19:33:22'),
(2, 'новый атрибут!', '2025-07-04 19:54:25', '2025-07-04 19:54:25'),
(3, 'asd', '2025-07-05 07:53:27', '2025-07-05 07:53:27'),
(4, 'фыв', '2025-07-05 08:03:04', '2025-07-05 08:03:04'),
(5, 'фыва', '2025-07-05 08:26:59', '2025-07-05 08:26:59'),
(6, 'фывааа', '2025-07-05 16:14:47', '2025-07-05 16:14:47');

-- --------------------------------------------------------

--
-- Структура таблицы `attribute_values`
--

CREATE TABLE `attribute_values` (
  `id` bigint UNSIGNED NOT NULL,
  `attribute_id` bigint UNSIGNED NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `attribute_values`
--

INSERT INTO `attribute_values` (`id`, `attribute_id`, `value`, `slug`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 3, 'фыв', 'fyv', 0, 1, '2025-07-05 15:50:42', '2025-07-05 15:50:42'),
(2, 3, '313', '313', 0, 1, '2025-07-05 18:24:10', '2025-07-05 18:24:10'),
(3, 3, 'aa', 'aa', 0, 1, '2025-07-05 19:49:04', '2025-07-05 19:49:04');

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE `categories` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `parent_id`, `sort_order`, `meta_title`, `meta_description`, `created_at`, `updated_at`) VALUES
(2, 'test', 'test', NULL, 0, NULL, NULL, '2025-07-04 19:52:27', '2025-07-04 19:52:27'),
(3, 'asdad', 'asdad', 2, 0, NULL, NULL, '2025-07-05 07:54:05', '2025-07-05 07:54:05'),
(4, 'фыв', 'fyv', 3, 0, NULL, NULL, '2025-07-05 08:27:38', '2025-07-05 08:27:38'),
(5, 'йцу', 'icu', 2, 0, NULL, NULL, '2025-07-05 09:11:04', '2025-07-05 09:11:04'),
(6, '1', '1', 4, 0, NULL, NULL, '2025-07-05 09:12:07', '2025-07-05 09:12:07'),
(7, '123123', '123123', 3, 0, NULL, NULL, '2025-07-05 16:15:03', '2025-07-06 12:18:19'),
(8, 'ыва', 'yva', 2, 0, NULL, NULL, '2025-07-05 16:43:18', '2025-07-05 16:43:18'),
(9, '12341245', '12341245', NULL, 0, NULL, NULL, '2025-07-05 16:43:37', '2025-07-05 16:43:37'),
(10, '11', '11', 9, 0, NULL, NULL, '2025-07-05 16:43:43', '2025-07-05 16:43:43'),
(11, '1414', '1414', 7, 0, NULL, NULL, '2025-07-05 18:24:03', '2025-07-05 18:24:03');

-- --------------------------------------------------------

--
-- Структура таблицы `images`
--

CREATE TABLE `images` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED DEFAULT NULL,
  `filename` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'product',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `images`
--

INSERT INTO `images` (`id`, `product_id`, `filename`, `is_main`, `sort_order`, `type`, `created_at`, `updated_at`) VALUES
(1, NULL, '1751744828_6869813cbe620.webp', 0, 0, 'product', '2025-07-05 19:47:08', '2025-07-05 19:47:08'),
(2, NULL, '1751744915_68698193676c0.webp', 0, 1, 'product', '2025-07-05 19:48:35', '2025-07-05 19:48:35'),
(3, NULL, '1751744915_686981938b475.webp', 0, 2, 'product', '2025-07-05 19:48:35', '2025-07-05 19:48:35'),
(4, NULL, '1751746418_6869877220bf8.webp', 0, 3, 'product', '2025-07-05 20:13:38', '2025-07-05 20:13:38'),
(5, NULL, '1751818284_686aa02cb7994.jpg', 0, 4, 'product', '2025-07-06 16:11:24', '2025-07-06 16:11:24');

-- --------------------------------------------------------

--
-- Структура таблицы `marketplace_maps`
--

CREATE TABLE `marketplace_maps` (
  `id` bigint UNSIGNED NOT NULL,
  `marketplace` enum('wildberries','ozon','yandex_market','flowwow') COLLATE utf8mb4_unicode_ci NOT NULL,
  `mapping_type` enum('category','attribute') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'attribute' COMMENT 'Тип маппинга',
  `our_id` bigint UNSIGNED NOT NULL,
  `marketplace_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `additional_data` json DEFAULT NULL COMMENT 'Дополнительные данные',
  `marketplace_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `marketplace_maps`
--

INSERT INTO `marketplace_maps` (`id`, `marketplace`, `mapping_type`, `our_id`, `marketplace_id`, `additional_data`, `marketplace_name`, `created_at`, `updated_at`) VALUES
(1, 'yandex_market', 'attribute', 3, 'Свет', NULL, 'Свет', '2025-07-06 11:10:59', '2025-07-06 11:10:59');

-- --------------------------------------------------------

--
-- Структура таблицы `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(2, '2025_01_01_000000_create_users_table', 1),
(3, '2025_01_01_000001_create_categories_table', 1),
(4, '2025_01_01_000002_create_themes_table', 1),
(5, '2025_01_01_000003_create_attributes_table', 1),
(6, '2025_01_01_000004_create_products_table', 1),
(7, '2025_01_01_000005_create_product_categories_table', 1),
(8, '2025_01_01_000006_create_product_attributes_table', 1),
(9, '2025_01_01_000007_create_marketplace_maps_table', 1),
(10, '2025_01_01_000007_create_quiz_rules_table', 1),
(11, '2025_01_02_000001_add_parent_id_to_categories_table', 2),
(12, '2025_01_02_000002_create_attribute_values_table', 2),
(13, '2025_07_05_224553_create_images_table_with_relations', 3),
(14, '2025_01_07_000002_update_marketplace_maps_table_fixed', 4);

-- --------------------------------------------------------

--
-- Структура таблицы `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\User', 1, 'admin-token', '062ddd08dc4360a7bffad9cc5ce43f63ea497140fbc22bab93ab8f31bacddf9f', '[\"*\"]', '2025-07-04 19:57:57', NULL, '2025-07-04 19:31:16', '2025-07-04 19:57:57'),
(2, 'App\\Models\\User', 1, 'admin-token', '332e80a84f401d9f240106be4c667ee04b6c4143fce5b2010f5fac428aed41cd', '[\"*\"]', '2025-07-06 16:11:28', NULL, '2025-07-04 19:58:09', '2025-07-06 16:11:28'),
(3, 'App\\Models\\User', 1, 'admin-token', '0e7c15c6855b77a26bf44eae74a2ddc74bcd6f1f30d1579e329e739e4f9dc0a8', '[\"*\"]', '2025-07-04 20:43:05', NULL, '2025-07-04 20:02:10', '2025-07-04 20:43:05'),
(4, 'App\\Models\\User', 1, 'admin-token', '9727d2cbe8d205f7ad1e3849735f6b78815874d35000d07bc0c3f7d9c3de3579', '[\"*\"]', '2025-07-05 06:34:20', NULL, '2025-07-05 06:24:58', '2025-07-05 06:34:20'),
(5, 'App\\Models\\User', 1, 'admin-token', 'e67e652dbaeea0f738761fb00a03a24a98c6befd229ddcb9c21535dd5cd3dddd', '[\"*\"]', '2025-07-05 08:37:37', NULL, '2025-07-05 06:34:23', '2025-07-05 08:37:37'),
(6, 'App\\Models\\User', 1, 'admin-token', '0a294824a6f1c820d305e4dd37b8fe1cddb82937072eec45c8aff5863f4085de', '[\"*\"]', '2025-07-05 11:03:40', NULL, '2025-07-05 08:36:56', '2025-07-05 11:03:40');

-- --------------------------------------------------------

--
-- Структура таблицы `products`
--

CREATE TABLE `products` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `use_matryoshka` tinyint(1) NOT NULL DEFAULT '0',
  `image_layers` json DEFAULT NULL,
  `gallery_images` json DEFAULT NULL,
  `theme_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `products`
--

INSERT INTO `products` (`id`, `name`, `slug`, `description`, `price`, `use_matryoshka`, `image_layers`, `gallery_images`, `theme_id`, `created_at`, `updated_at`) VALUES
(2, 'фывфыв', 'fyvfyv', 'фывф', '2455.00', 1, '{\"inner\": \"1751739780_XAktDWhD2k.webp\", \"outer\": \"1751739776_sTlJlWQcLf.webp\"}', '[\"1751657485_68682c0dea845.jpg\", \"1751733624_uvLGMrckzm.webp\"]', 2, '2025-07-05 15:49:03', '2025-07-05 18:25:17'),
(3, 'фывфыв', 'fyvfyv-1', 'фыв', '123.00', 1, '{\"inner\": \"1751744915_68698193676c0.webp\", \"outer\": \"1751744915_686981938b475.webp\"}', '[\"1751744828_6869813cbe620.webp\"]', 4, '2025-07-05 16:42:35', '2025-07-05 19:51:10');

-- --------------------------------------------------------

--
-- Структура таблицы `product_attributes`
--

CREATE TABLE `product_attributes` (
  `product_id` bigint UNSIGNED NOT NULL,
  `attribute_id` bigint UNSIGNED NOT NULL,
  `attribute_value_id` bigint UNSIGNED DEFAULT NULL,
  `custom_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `product_attributes`
--

INSERT INTO `product_attributes` (`product_id`, `attribute_id`, `attribute_value_id`, `custom_value`) VALUES
(2, 1, NULL, 'фыв'),
(2, 2, NULL, 'фыв'),
(2, 3, NULL, 'фыв'),
(2, 4, NULL, 'фыв'),
(2, 5, NULL, 'в'),
(3, 3, NULL, 'фыв');

-- --------------------------------------------------------

--
-- Структура таблицы `product_categories`
--

CREATE TABLE `product_categories` (
  `product_id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `product_categories`
--

INSERT INTO `product_categories` (`product_id`, `category_id`) VALUES
(3, 11);

-- --------------------------------------------------------

--
-- Структура таблицы `quiz_rules`
--

CREATE TABLE `quiz_rules` (
  `id` bigint UNSIGNED NOT NULL,
  `month` int NOT NULL COMMENT 'Месяц рождения (1-12)',
  `day` int NOT NULL COMMENT 'День рождения (1-31)',
  `hour_start` int NOT NULL COMMENT 'Начальный час (0-23)',
  `hour_end` int NOT NULL COMMENT 'Конечный час (0-23)',
  `stones` json NOT NULL COMMENT 'Массив рекомендуемых камней',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `themes`
--

CREATE TABLE `themes` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `themes`
--

INSERT INTO `themes` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'новая', '2025-07-04 19:33:16', '2025-07-04 19:33:16'),
(2, 'asd1', '2025-07-04 19:44:59', '2025-07-05 09:11:46'),
(3, 'фыв', '2025-07-04 19:48:56', '2025-07-04 19:48:56'),
(4, 'кпн', '2025-07-05 07:53:08', '2025-07-05 07:53:08'),
(5, 'фывф', '2025-07-05 08:27:31', '2025-07-05 08:27:31'),
(6, 'ыва', '2025-07-05 11:34:46', '2025-07-05 11:34:46'),
(7, '123', '2025-07-05 16:14:53', '2025-07-05 16:14:53'),
(8, 'фыв23', '2025-07-05 18:23:57', '2025-07-05 18:23:57');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@stuj.ru', NULL, '$2y$12$/K9vncpHNvfm60ZY5hLaQOczw2rP6qoypIbI1QthIb0WSLpt0dPAC', NULL, '2025-07-04 19:31:00', '2025-07-04 19:32:37');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `attributes`
--
ALTER TABLE `attributes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attributes_name_index` (`name`);

--
-- Индексы таблицы `attribute_values`
--
ALTER TABLE `attribute_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attribute_values_attribute_id_value_unique` (`attribute_id`,`value`),
  ADD KEY `attribute_values_attribute_id_index` (`attribute_id`),
  ADD KEY `attribute_values_slug_index` (`slug`),
  ADD KEY `attribute_values_sort_order_index` (`sort_order`),
  ADD KEY `attribute_values_is_active_index` (`is_active`);

--
-- Индексы таблицы `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`),
  ADD KEY `categories_slug_index` (`slug`),
  ADD KEY `categories_parent_id_index` (`parent_id`),
  ADD KEY `categories_sort_order_index` (`sort_order`);

--
-- Индексы таблицы `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `images_product_id_is_main_index` (`product_id`,`is_main`),
  ADD KEY `images_product_id_sort_order_index` (`product_id`,`sort_order`);

--
-- Индексы таблицы `marketplace_maps`
--
ALTER TABLE `marketplace_maps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_mapping` (`marketplace`,`mapping_type`,`our_id`),
  ADD KEY `marketplace_maps_marketplace_index` (`marketplace`),
  ADD KEY `marketplace_maps_our_attr_id_index` (`our_id`),
  ADD KEY `marketplace_mapping_type_index` (`marketplace`,`mapping_type`),
  ADD KEY `marketplace_our_id_index` (`marketplace`,`our_id`);

--
-- Индексы таблицы `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Индексы таблицы `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_slug_unique` (`slug`),
  ADD KEY `products_slug_index` (`slug`),
  ADD KEY `products_theme_id_index` (`theme_id`),
  ADD KEY `products_price_index` (`price`),
  ADD KEY `products_use_matryoshka_index` (`use_matryoshka`);

--
-- Индексы таблицы `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD PRIMARY KEY (`product_id`,`attribute_id`),
  ADD KEY `product_attributes_product_id_index` (`product_id`),
  ADD KEY `product_attributes_attribute_id_index` (`attribute_id`),
  ADD KEY `product_attributes_attribute_value_id_index` (`attribute_value_id`);

--
-- Индексы таблицы `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`product_id`,`category_id`),
  ADD KEY `product_categories_product_id_index` (`product_id`),
  ADD KEY `product_categories_category_id_index` (`category_id`);

--
-- Индексы таблицы `quiz_rules`
--
ALTER TABLE `quiz_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_rules_month_day_index` (`month`,`day`),
  ADD KEY `quiz_rules_hour_start_hour_end_index` (`hour_start`,`hour_end`);

--
-- Индексы таблицы `themes`
--
ALTER TABLE `themes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `themes_name_index` (`name`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `attributes`
--
ALTER TABLE `attributes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `attribute_values`
--
ALTER TABLE `attribute_values`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `images`
--
ALTER TABLE `images`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `marketplace_maps`
--
ALTER TABLE `marketplace_maps`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT для таблицы `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `quiz_rules`
--
ALTER TABLE `quiz_rules`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `themes`
--
ALTER TABLE `themes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `attribute_values`
--
ALTER TABLE `attribute_values`
  ADD CONSTRAINT `attribute_values_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `images`
--
ALTER TABLE `images`
  ADD CONSTRAINT `images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_theme_id_foreign` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD CONSTRAINT `product_attributes_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_attributes_attribute_value_id_foreign` FOREIGN KEY (`attribute_value_id`) REFERENCES `attribute_values` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_attributes_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `product_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_categories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
