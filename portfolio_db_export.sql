-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: portfolio_db
-- ------------------------------------------------------
-- Server version	8.0.42

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `awards`
--

DROP TABLE IF EXISTS `awards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `awards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `organization` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `award_year` year NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_awards_year` (`award_year`),
  KEY `idx_awards_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `awards`
--

LOCK TABLES `awards` WRITE;
/*!40000 ALTER TABLE `awards` DISABLE KEYS */;
/*!40000 ALTER TABLE `awards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `category` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'General',
  `thumbnail_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('published','draft','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `views` bigint unsigned NOT NULL DEFAULT '0',
  `project_id` bigint unsigned DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_blog_posts_slug` (`slug`),
  KEY `idx_bp_status` (`status`),
  KEY `idx_bp_category` (`category`),
  KEY `idx_bp_created` (`created_at`),
  KEY `idx_bp_views` (`views`),
  KEY `fk_bp_project` (`project_id`),
  CONSTRAINT `fk_bp_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_posts`
--

LOCK TABLES `blog_posts` WRITE;
/*!40000 ALTER TABLE `blog_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `blog_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('Website Inquiry','Collaboration','Project Proposal','Job Opportunity','Other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Other',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('unread','read','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unread',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cm_status` (`status`),
  KEY `idx_cm_category` (`category`),
  KEY `idx_cm_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
INSERT INTO `contact_messages` VALUES (1,'Test User','test@example.com','Other','Debug','Direct test message.','read','::1','2026-03-22 18:16:00','2026-03-22 09:56:47','2026-03-22 10:16:00'),(2,'Test User','test@example.com','Other','Debug','Direct test message.','read','::1','2026-03-22 18:16:02','2026-03-22 09:56:47','2026-03-22 10:16:02'),(3,'Test User','test@example.com','Other','API Test Suite','Automated test message please disregard.','read','::1','2026-03-22 18:15:58','2026-03-22 09:59:45','2026-03-22 10:15:58'),(4,'Bench Runner','bench@example.com','Other','Benchmark','Automated benchmark test run.','read','::1','2026-03-22 18:15:46','2026-03-22 09:59:47','2026-03-22 10:15:46'),(5,'Bench Runner','bench@example.com','Other','Benchmark','Automated benchmark test run.','read','::1','2026-03-22 18:15:49','2026-03-22 09:59:47','2026-03-22 10:15:49'),(6,'Bench Runner','bench@example.com','Other','Benchmark','Automated benchmark test run.','read','::1','2026-03-22 18:15:51','2026-03-22 09:59:47','2026-03-22 10:15:51'),(7,'Bench Runner','bench@example.com','Other','Benchmark','Automated benchmark test run.','read','::1','2026-03-22 18:15:53','2026-03-22 09:59:47','2026-03-22 10:15:53'),(8,'Bench Runner','bench@example.com','Other','Benchmark','Automated benchmark test run.','read','::1','2026-03-22 18:15:55','2026-03-22 09:59:47','2026-03-22 10:15:55'),(9,'Test User','test@example.com','Other','API Test Suite','Automated test message please disregard.','read','::1','2026-03-22 18:15:44','2026-03-22 10:00:38','2026-03-22 10:15:44'),(10,'Bench Runner','bench@example.com','Other','Benchmark','Automated benchmark test run.','read','::1','2026-03-22 18:15:22','2026-03-22 10:00:40','2026-03-22 10:15:22'),(11,'Bench Runner','bench@example.com','Other','Benchmark','Automated benchmark test run.','read','::1','2026-03-22 18:15:34','2026-03-22 10:00:40','2026-03-22 10:15:34'),(12,'Bench Runner','bench@example.com','Other','Benchmark','Automated benchmark test run.','read','::1','2026-03-22 18:15:37','2026-03-22 10:00:40','2026-03-22 10:15:37'),(13,'Bench Runner','bench@example.com','Other','Benchmark','Automated benchmark test run.','read','::1','2026-03-22 18:15:39','2026-03-22 10:00:40','2026-03-22 10:15:39'),(14,'Bench Runner','bench@example.com','Other','Benchmark','Automated benchmark test run.','read','::1','2026-03-22 18:15:41','2026-03-22 10:00:40','2026-03-22 10:15:41'),(15,'Test User','test@example.com','Other','API Test Suite','Automated test message please disregard.','read','::1','2026-03-22 18:15:19','2026-03-22 10:01:52','2026-03-22 10:15:19'),(16,'Bench Runner','bench@example.com','Other','Benchmark','Automated benchmark test run.','read','::1','2026-03-22 18:15:02','2026-03-22 10:01:54','2026-03-22 10:15:02'),(17,'Bench Runner','bench@example.com','Other','Benchmark','Automated benchmark test run.','read','::1','2026-03-22 18:15:05','2026-03-22 10:01:54','2026-03-22 10:15:05'),(18,'Bench Runner','bench@example.com','Other','Benchmark','Automated benchmark test run.','read','::1','2026-03-22 18:15:07','2026-03-22 10:01:54','2026-03-22 10:15:07'),(19,'Bench Runner','bench@example.com','Other','Benchmark','Automated benchmark test run.','read','::1','2026-03-22 18:15:10','2026-03-22 10:01:54','2026-03-22 10:15:10'),(20,'Bench Runner','bench@example.com','Other','Benchmark','Automated benchmark test run.','read','::1','2026-03-22 18:15:16','2026-03-22 10:01:54','2026-03-22 10:15:16');
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_otps`
--

DROP TABLE IF EXISTS `login_otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_otps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `otp_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_otps_user_created` (`user_id`,`created_at`),
  CONSTRAINT `fk_login_otps_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_otps`
--

LOCK TABLES `login_otps` WRITE;
/*!40000 ALTER TABLE `login_otps` DISABLE KEYS */;
INSERT INTO `login_otps` VALUES (1,1,'4caef32dfd390e23c8e91212055d1ebfe393d603f0035dcedc7e3b05d1dd8518',0,'2026-03-09 17:02:51','2026-03-10 01:03:47','2026-03-09 17:01:51'),(2,1,'da21e8c52c0fb465ff0c077312b7232bb0d6c48e17b9f2ba5ce1dc991785ef46',0,'2026-03-09 17:04:47','2026-03-10 01:04:09','2026-03-09 17:03:47'),(3,1,'8837e92a09d655aa3f77a6b210dca6b96dfe3f60ec847cf5dcf42e0907925716',0,'2026-03-09 17:38:59','2026-03-10 01:38:21','2026-03-09 17:37:59'),(4,1,'aa48cc706d02beac94831313cc520dac830d82242c40c0340e8990ca804a6c41',0,'2026-03-13 10:37:18','2026-03-13 18:36:42','2026-03-13 10:36:18'),(5,1,'a6acec2d09b187dcb52e2048a19dd79fa5c813cd960a7c5cb27c1a891ff470c2',0,'2026-03-14 04:42:47','2026-03-14 12:42:18','2026-03-14 04:41:47'),(6,1,'d5f326af7b57da2da620b039f2c98b2173b66509b226f6c88d434b0fe26a814f',0,'2026-03-14 08:19:45','2026-03-14 16:19:53','2026-03-14 08:18:45'),(7,1,'cf73680c7c37d88a1fb808c33972a647a2ac2c60c93bc8fbd3dcd8bb6feee8fc',0,'2026-03-14 08:20:53','2026-03-14 16:20:45','2026-03-14 08:19:53'),(8,1,'b93dfa54f6b09c383328704f94297d0d249a7bedfd67190212cf1a5e7e8f5521',0,'2026-03-15 14:21:54','2026-03-15 22:21:41','2026-03-15 14:20:54'),(9,1,'0876eeddd75581383161f9faef8d0597c99e577c71a2c637784f383bddda6acf',0,'2026-03-16 08:03:14','2026-03-16 16:02:35','2026-03-16 08:02:14'),(10,1,'c22c62a112306bdb93319a5779c590e4e88fdee582245c5eeb76ae34b5a87fb1',0,'2026-03-17 00:54:26','2026-03-17 08:53:37','2026-03-17 00:53:26'),(11,1,'94648a4fd22953fa112001873f45013a23762f6430b52493d0c6b49e5d66df82',0,'2026-03-20 04:50:16','2026-03-20 12:50:01','2026-03-20 04:49:16'),(12,1,'3c568b9fd8e93b335819f9b524342a91dd978c43d7a390b49212e00c9bf12169',0,'2026-03-20 04:59:47','2026-03-20 12:59:10','2026-03-20 04:58:47'),(13,1,'46c1bb24ebb59f02004ec4dfd90ab4b658dedfc39489222be0e55b9600c2b473',0,'2026-03-20 05:00:38','2026-03-20 12:59:54','2026-03-20 04:59:38'),(14,1,'5e9885d2e1c56f08f74391c7944550baadb734bbae8482abbc294bc8929f40c4',0,'2026-03-20 05:06:08','2026-03-20 13:05:29','2026-03-20 05:05:08'),(15,1,'fdafdcfd6ae81309c88be477ccc750798f3b0bbf8c35c33a5fb1815b996f39e3',0,'2026-03-20 05:10:25','2026-03-20 13:09:38','2026-03-20 05:09:25'),(16,1,'98c2c09a5213c0c59eb9fba283b0b3598fde13fc2e6c1d9dfd92523fb1004018',0,'2026-03-20 05:19:09','2026-03-20 13:18:23','2026-03-20 05:18:09'),(17,1,'f713ebb927a4a392d21f361f2e9f91e178cb44d233e09881f94e6860bb5637c7',0,'2026-03-21 10:36:51','2026-03-21 18:36:47','2026-03-21 10:35:51'),(18,1,'bf129c03c8d8d73689e88e1c6bb31d267a3632d6a4cd9ec67f7abf074b81ea00',0,'2026-03-22 09:28:43','2026-03-22 17:28:00','2026-03-22 09:27:43');
/*!40000 ALTER TABLE `login_otps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portfolio_entries`
--

DROP TABLE IF EXISTS `portfolio_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portfolio_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entry_key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_type` enum('text','json') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `entry_value` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_portfolio_entries_key` (`entry_key`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portfolio_entries`
--

LOCK TABLES `portfolio_entries` WRITE;
/*!40000 ALTER TABLE `portfolio_entries` DISABLE KEYS */;
INSERT INTO `portfolio_entries` VALUES (1,'social_links','json','{\"email\":\"llanetacristianpastoril@gmail.com\",\"github\":\"https://github.com/CrazySloths\",\"linkedin\":\"https://www.linkedin.com/in/cristian-mark-angelo-llaneta-9b5024275/\",\"facebook\":\"https://www.facebook.com/cristianmarkangelo.llaneta\",\"instagram\":\"https://www.instagram.com/yamijishiyurei/\",\"twitter\":\"\",\"telegram\":\"\",\"discord\":\"https://discord.com/users/1162274659094503464\",\"viber\":\"viber://chat?number=%2B639515691003\",\"whatsapp\":\"\"}','2026-03-20 09:50:21','2026-03-21 17:29:22'),(2,'name','text','Cristian Mark Angelo Llaneta','2026-03-20 10:01:25','2026-03-20 10:01:25'),(3,'bio','text','I am full-stack developer focused on building structured, scalable, and efficient management system. I specialize in backend development, database design, and responsive user interfaces.','2026-03-20 10:01:25','2026-03-20 10:01:25'),(4,'cv_url','text','','2026-03-20 10:01:25','2026-03-21 16:15:21'),(15,'phone_discord','text','09934080709','2026-03-21 17:16:20','2026-03-21 17:16:20'),(16,'phone_tnt','text','09515691003','2026-03-21 17:16:20','2026-03-21 17:28:58'),(17,'phone_globe','text','','2026-03-21 17:16:20','2026-03-21 17:16:20');
/*!40000 ALTER TABLE `portfolio_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portfolio_items`
--

DROP TABLE IF EXISTS `portfolio_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portfolio_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_portfolio_items_category_sort` (`category`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portfolio_items`
--

LOCK TABLES `portfolio_items` WRITE;
/*!40000 ALTER TABLE `portfolio_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `portfolio_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_technologies`
--

DROP TABLE IF EXISTS `project_technologies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_technologies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `technology` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pt_technology` (`technology`),
  KEY `fk_pt_project` (`project_id`),
  CONSTRAINT `fk_pt_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_technologies`
--

LOCK TABLES `project_technologies` WRITE;
/*!40000 ALTER TABLE `project_technologies` DISABLE KEYS */;
INSERT INTO `project_technologies` VALUES (36,1,'Alpine.js'),(37,1,'CSS'),(38,1,'Docker'),(39,1,'Git'),(40,1,'JavaScript'),(41,1,'Laravel'),(42,1,'Node.js'),(43,1,'PHP'),(44,1,'Tailwind CSS'),(45,1,'HTML'),(46,1,'MySQL'),(47,1,'PostgreSQL'),(48,1,'SQLite');
/*!40000 ALTER TABLE `project_technologies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('published','draft','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `category` enum('Web Systems','Research','UI/UX','Mobile') COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `github_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `views` bigint unsigned NOT NULL DEFAULT '0',
  `comments_count` int unsigned NOT NULL DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_projects_status` (`status`),
  KEY `idx_projects_category` (`category`),
  KEY `idx_projects_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES (1,'Public Facilities Reservation System','LGU public facility reservation system with multi-level approval, conflict detection, and AI-powered pattern recognition.','published','Web Systems','./assets/uploads/projects/proj_69bd5a77544ac7.47869517.png','https://github.com/CrazySloths/reservation-system.git','https://facilities.local-government-unit-1-ph.com/',0,0,NULL,'2026-03-20 14:32:23','2026-03-21 14:14:32');
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','editor') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Cristian Mark Angelo P. Llaneta','llanetacristianpastoril@gmail.com','$2y$10$U1.6jHxjNN/Y.8DOSb9pH.Ygi8CrAmc9W.wys/e9AUM8Zrz/iI1Ja','admin',1,'2026-03-09 16:44:27','2026-03-09 16:44:27');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visitors`
--

DROP TABLE IF EXISTS `visitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `visitors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `page` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '/',
  `referrer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_seconds` int unsigned DEFAULT NULL,
  `visited_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_v_session` (`session_id`),
  KEY `idx_v_visited` (`visited_at`),
  KEY `idx_v_page` (`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitors`
--

LOCK TABLES `visitors` WRITE;
/*!40000 ALTER TABLE `visitors` DISABLE KEYS */;
/*!40000 ALTER TABLE `visitors` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-08 21:14:10
