-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 08, 2026 at 01:03 PM
-- Server version: 8.0.42
-- PHP Version: 8.3.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `portfolio_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `awards`
--

CREATE TABLE `awards` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `organization` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `award_year` year NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `category` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'General',
  `thumbnail_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('published','draft','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `views` bigint UNSIGNED NOT NULL DEFAULT '0',
  `project_id` bigint UNSIGNED DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('Website Inquiry','Collaboration','Project Proposal','Job Opportunity','Other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Other',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('unread','read','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unread',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `category`, `subject`, `message`, `status`, `ip_address`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Test User', 'test@example.com', 'Other', 'Debug', 'Direct test message.', 'read', '::1', '2026-03-22 18:16:00', '2026-03-22 09:56:47', '2026-03-22 10:16:00'),
(2, 'Test User', 'test@example.com', 'Other', 'Debug', 'Direct test message.', 'read', '::1', '2026-03-22 18:16:02', '2026-03-22 09:56:47', '2026-03-22 10:16:02'),
(3, 'Test User', 'test@example.com', 'Other', 'API Test Suite', 'Automated test message please disregard.', 'read', '::1', '2026-03-22 18:15:58', '2026-03-22 09:59:45', '2026-03-22 10:15:58'),
(4, 'Bench Runner', 'bench@example.com', 'Other', 'Benchmark', 'Automated benchmark test run.', 'read', '::1', '2026-03-22 18:15:46', '2026-03-22 09:59:47', '2026-03-22 10:15:46'),
(5, 'Bench Runner', 'bench@example.com', 'Other', 'Benchmark', 'Automated benchmark test run.', 'read', '::1', '2026-03-22 18:15:49', '2026-03-22 09:59:47', '2026-03-22 10:15:49'),
(6, 'Bench Runner', 'bench@example.com', 'Other', 'Benchmark', 'Automated benchmark test run.', 'read', '::1', '2026-03-22 18:15:51', '2026-03-22 09:59:47', '2026-03-22 10:15:51'),
(7, 'Bench Runner', 'bench@example.com', 'Other', 'Benchmark', 'Automated benchmark test run.', 'read', '::1', '2026-03-22 18:15:53', '2026-03-22 09:59:47', '2026-03-22 10:15:53'),
(8, 'Bench Runner', 'bench@example.com', 'Other', 'Benchmark', 'Automated benchmark test run.', 'read', '::1', '2026-03-22 18:15:55', '2026-03-22 09:59:47', '2026-03-22 10:15:55'),
(9, 'Test User', 'test@example.com', 'Other', 'API Test Suite', 'Automated test message please disregard.', 'read', '::1', '2026-03-22 18:15:44', '2026-03-22 10:00:38', '2026-03-22 10:15:44'),
(10, 'Bench Runner', 'bench@example.com', 'Other', 'Benchmark', 'Automated benchmark test run.', 'read', '::1', '2026-03-22 18:15:22', '2026-03-22 10:00:40', '2026-03-22 10:15:22'),
(11, 'Bench Runner', 'bench@example.com', 'Other', 'Benchmark', 'Automated benchmark test run.', 'read', '::1', '2026-03-22 18:15:34', '2026-03-22 10:00:40', '2026-03-22 10:15:34'),
(12, 'Bench Runner', 'bench@example.com', 'Other', 'Benchmark', 'Automated benchmark test run.', 'read', '::1', '2026-03-22 18:15:37', '2026-03-22 10:00:40', '2026-03-22 10:15:37'),
(13, 'Bench Runner', 'bench@example.com', 'Other', 'Benchmark', 'Automated benchmark test run.', 'read', '::1', '2026-03-22 18:15:39', '2026-03-22 10:00:40', '2026-03-22 10:15:39'),
(14, 'Bench Runner', 'bench@example.com', 'Other', 'Benchmark', 'Automated benchmark test run.', 'read', '::1', '2026-03-22 18:15:41', '2026-03-22 10:00:40', '2026-03-22 10:15:41'),
(15, 'Test User', 'test@example.com', 'Other', 'API Test Suite', 'Automated test message please disregard.', 'read', '::1', '2026-03-22 18:15:19', '2026-03-22 10:01:52', '2026-03-22 10:15:19'),
(16, 'Bench Runner', 'bench@example.com', 'Other', 'Benchmark', 'Automated benchmark test run.', 'read', '::1', '2026-03-22 18:15:02', '2026-03-22 10:01:54', '2026-03-22 10:15:02'),
(17, 'Bench Runner', 'bench@example.com', 'Other', 'Benchmark', 'Automated benchmark test run.', 'read', '::1', '2026-03-22 18:15:05', '2026-03-22 10:01:54', '2026-03-22 10:15:05'),
(18, 'Bench Runner', 'bench@example.com', 'Other', 'Benchmark', 'Automated benchmark test run.', 'read', '::1', '2026-03-22 18:15:07', '2026-03-22 10:01:54', '2026-03-22 10:15:07'),
(19, 'Bench Runner', 'bench@example.com', 'Other', 'Benchmark', 'Automated benchmark test run.', 'read', '::1', '2026-03-22 18:15:10', '2026-03-22 10:01:54', '2026-03-22 10:15:10'),
(20, 'Bench Runner', 'bench@example.com', 'Other', 'Benchmark', 'Automated benchmark test run.', 'read', '::1', '2026-03-22 18:15:16', '2026-03-22 10:01:54', '2026-03-22 10:15:16');

-- --------------------------------------------------------

--
-- Table structure for table `login_otps`
--

CREATE TABLE `login_otps` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `otp_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_otps`
--

INSERT INTO `login_otps` (`id`, `user_id`, `otp_hash`, `attempts`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 1, '4caef32dfd390e23c8e91212055d1ebfe393d603f0035dcedc7e3b05d1dd8518', 0, '2026-03-09 17:02:51', '2026-03-10 01:03:47', '2026-03-09 17:01:51'),
(2, 1, 'da21e8c52c0fb465ff0c077312b7232bb0d6c48e17b9f2ba5ce1dc991785ef46', 0, '2026-03-09 17:04:47', '2026-03-10 01:04:09', '2026-03-09 17:03:47'),
(3, 1, '8837e92a09d655aa3f77a6b210dca6b96dfe3f60ec847cf5dcf42e0907925716', 0, '2026-03-09 17:38:59', '2026-03-10 01:38:21', '2026-03-09 17:37:59'),
(4, 1, 'aa48cc706d02beac94831313cc520dac830d82242c40c0340e8990ca804a6c41', 0, '2026-03-13 10:37:18', '2026-03-13 18:36:42', '2026-03-13 10:36:18'),
(5, 1, 'a6acec2d09b187dcb52e2048a19dd79fa5c813cd960a7c5cb27c1a891ff470c2', 0, '2026-03-14 04:42:47', '2026-03-14 12:42:18', '2026-03-14 04:41:47'),
(6, 1, 'd5f326af7b57da2da620b039f2c98b2173b66509b226f6c88d434b0fe26a814f', 0, '2026-03-14 08:19:45', '2026-03-14 16:19:53', '2026-03-14 08:18:45'),
(7, 1, 'cf73680c7c37d88a1fb808c33972a647a2ac2c60c93bc8fbd3dcd8bb6feee8fc', 0, '2026-03-14 08:20:53', '2026-03-14 16:20:45', '2026-03-14 08:19:53'),
(8, 1, 'b93dfa54f6b09c383328704f94297d0d249a7bedfd67190212cf1a5e7e8f5521', 0, '2026-03-15 14:21:54', '2026-03-15 22:21:41', '2026-03-15 14:20:54'),
(9, 1, '0876eeddd75581383161f9faef8d0597c99e577c71a2c637784f383bddda6acf', 0, '2026-03-16 08:03:14', '2026-03-16 16:02:35', '2026-03-16 08:02:14'),
(10, 1, 'c22c62a112306bdb93319a5779c590e4e88fdee582245c5eeb76ae34b5a87fb1', 0, '2026-03-17 00:54:26', '2026-03-17 08:53:37', '2026-03-17 00:53:26'),
(11, 1, '94648a4fd22953fa112001873f45013a23762f6430b52493d0c6b49e5d66df82', 0, '2026-03-20 04:50:16', '2026-03-20 12:50:01', '2026-03-20 04:49:16'),
(12, 1, '3c568b9fd8e93b335819f9b524342a91dd978c43d7a390b49212e00c9bf12169', 0, '2026-03-20 04:59:47', '2026-03-20 12:59:10', '2026-03-20 04:58:47'),
(13, 1, '46c1bb24ebb59f02004ec4dfd90ab4b658dedfc39489222be0e55b9600c2b473', 0, '2026-03-20 05:00:38', '2026-03-20 12:59:54', '2026-03-20 04:59:38'),
(14, 1, '5e9885d2e1c56f08f74391c7944550baadb734bbae8482abbc294bc8929f40c4', 0, '2026-03-20 05:06:08', '2026-03-20 13:05:29', '2026-03-20 05:05:08'),
(15, 1, 'fdafdcfd6ae81309c88be477ccc750798f3b0bbf8c35c33a5fb1815b996f39e3', 0, '2026-03-20 05:10:25', '2026-03-20 13:09:38', '2026-03-20 05:09:25'),
(16, 1, '98c2c09a5213c0c59eb9fba283b0b3598fde13fc2e6c1d9dfd92523fb1004018', 0, '2026-03-20 05:19:09', '2026-03-20 13:18:23', '2026-03-20 05:18:09'),
(17, 1, 'f713ebb927a4a392d21f361f2e9f91e178cb44d233e09881f94e6860bb5637c7', 0, '2026-03-21 10:36:51', '2026-03-21 18:36:47', '2026-03-21 10:35:51'),
(18, 1, 'bf129c03c8d8d73689e88e1c6bb31d267a3632d6a4cd9ec67f7abf074b81ea00', 0, '2026-03-22 09:28:43', '2026-03-22 17:28:00', '2026-03-22 09:27:43');

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_entries`
--

CREATE TABLE `portfolio_entries` (
  `id` bigint UNSIGNED NOT NULL,
  `entry_key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_type` enum('text','json') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `entry_value` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `portfolio_entries`
--

INSERT INTO `portfolio_entries` (`id`, `entry_key`, `entry_type`, `entry_value`, `created_at`, `updated_at`) VALUES
(1, 'social_links', 'json', '{\"email\":\"llanetacristianpastoril@gmail.com\",\"github\":\"https://github.com/CrazySloths\",\"linkedin\":\"https://www.linkedin.com/in/cristian-mark-angelo-llaneta-9b5024275/\",\"facebook\":\"https://www.facebook.com/cristianmarkangelo.llaneta\",\"instagram\":\"https://www.instagram.com/yamijishiyurei/\",\"twitter\":\"\",\"telegram\":\"\",\"discord\":\"https://discord.com/users/1162274659094503464\",\"viber\":\"viber://chat?number=%2B639515691003\",\"whatsapp\":\"\"}', '2026-03-20 09:50:21', '2026-03-21 17:29:22'),
(2, 'name', 'text', 'Cristian Mark Angelo Llaneta', '2026-03-20 10:01:25', '2026-03-20 10:01:25'),
(3, 'bio', 'text', 'I am full-stack developer focused on building structured, scalable, and efficient management system. I specialize in backend development, database design, and responsive user interfaces.', '2026-03-20 10:01:25', '2026-03-20 10:01:25'),
(4, 'cv_url', 'text', '', '2026-03-20 10:01:25', '2026-03-21 16:15:21'),
(15, 'phone_discord', 'text', '09934080709', '2026-03-21 17:16:20', '2026-03-21 17:16:20'),
(16, 'phone_tnt', 'text', '09515691003', '2026-03-21 17:16:20', '2026-03-21 17:28:58'),
(17, 'phone_globe', 'text', '', '2026-03-21 17:16:20', '2026-03-21 17:16:20');

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_items`
--

CREATE TABLE `portfolio_items` (
  `id` bigint UNSIGNED NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('published','draft','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `category` enum('Web Systems','Research','UI/UX','Mobile') COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `github_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `views` bigint UNSIGNED NOT NULL DEFAULT '0',
  `comments_count` int UNSIGNED NOT NULL DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `title`, `description`, `status`, `category`, `thumbnail_url`, `github_url`, `project_url`, `views`, `comments_count`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Public Facilities Reservation System', 'LGU public facility reservation system with multi-level approval, conflict detection, and AI-powered pattern recognition.', 'published', 'Web Systems', './assets/uploads/projects/proj_69bd5a77544ac7.47869517.png', 'https://github.com/CrazySloths/reservation-system.git', 'https://facilities.local-government-unit-1-ph.com/', 0, 0, NULL, '2026-03-20 14:32:23', '2026-03-21 14:14:32');

-- --------------------------------------------------------

--
-- Table structure for table `project_technologies`
--

CREATE TABLE `project_technologies` (
  `id` bigint UNSIGNED NOT NULL,
  `project_id` bigint UNSIGNED NOT NULL,
  `technology` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_technologies`
--

INSERT INTO `project_technologies` (`id`, `project_id`, `technology`) VALUES
(36, 1, 'Alpine.js'),
(37, 1, 'CSS'),
(38, 1, 'Docker'),
(39, 1, 'Git'),
(40, 1, 'JavaScript'),
(41, 1, 'Laravel'),
(42, 1, 'Node.js'),
(43, 1, 'PHP'),
(44, 1, 'Tailwind CSS'),
(45, 1, 'HTML'),
(46, 1, 'MySQL'),
(47, 1, 'PostgreSQL'),
(48, 1, 'SQLite');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `full_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','editor') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Cristian Mark Angelo P. Llaneta', 'llanetacristianpastoril@gmail.com', '$2y$10$U1.6jHxjNN/Y.8DOSb9pH.Ygi8CrAmc9W.wys/e9AUM8Zrz/iI1Ja', 'admin', 1, '2026-03-09 16:44:27', '2026-03-09 16:44:27');

-- --------------------------------------------------------

--
-- Table structure for table `visitors`
--

CREATE TABLE `visitors` (
  `id` bigint UNSIGNED NOT NULL,
  `session_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `page` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '/',
  `referrer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_seconds` int UNSIGNED DEFAULT NULL,
  `visited_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `awards`
--
ALTER TABLE `awards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_awards_year` (`award_year`),
  ADD KEY `idx_awards_sort` (`sort_order`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_blog_posts_slug` (`slug`),
  ADD KEY `idx_bp_status` (`status`),
  ADD KEY `idx_bp_category` (`category`),
  ADD KEY `idx_bp_created` (`created_at`),
  ADD KEY `idx_bp_views` (`views`),
  ADD KEY `fk_bp_project` (`project_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cm_status` (`status`),
  ADD KEY `idx_cm_category` (`category`),
  ADD KEY `idx_cm_created` (`created_at`);

--
-- Indexes for table `login_otps`
--
ALTER TABLE `login_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_otps_user_created` (`user_id`,`created_at`);

--
-- Indexes for table `portfolio_entries`
--
ALTER TABLE `portfolio_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_portfolio_entries_key` (`entry_key`);

--
-- Indexes for table `portfolio_items`
--
ALTER TABLE `portfolio_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_portfolio_items_category_sort` (`category`,`sort_order`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_projects_status` (`status`),
  ADD KEY `idx_projects_category` (`category`),
  ADD KEY `idx_projects_created` (`created_at`);

--
-- Indexes for table `project_technologies`
--
ALTER TABLE `project_technologies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pt_technology` (`technology`),
  ADD KEY `fk_pt_project` (`project_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- Indexes for table `visitors`
--
ALTER TABLE `visitors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_v_session` (`session_id`),
  ADD KEY `idx_v_visited` (`visited_at`),
  ADD KEY `idx_v_page` (`page`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `awards`
--
ALTER TABLE `awards`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `login_otps`
--
ALTER TABLE `login_otps`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `portfolio_entries`
--
ALTER TABLE `portfolio_entries`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `portfolio_items`
--
ALTER TABLE `portfolio_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `project_technologies`
--
ALTER TABLE `project_technologies`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `visitors`
--
ALTER TABLE `visitors`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD CONSTRAINT `fk_bp_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `login_otps`
--
ALTER TABLE `login_otps`
  ADD CONSTRAINT `fk_login_otps_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_technologies`
--
ALTER TABLE `project_technologies`
  ADD CONSTRAINT `fk_pt_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
