CREATE DATABASE IF NOT EXISTS portfolio_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE portfolio_db;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'editor') NOT NULL DEFAULT 'admin',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_otps (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  otp_hash CHAR(64) NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_login_otps_user_created (user_id, created_at),
  CONSTRAINT fk_login_otps_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS portfolio_entries (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entry_key VARCHAR(120) NOT NULL,
  entry_type ENUM('text', 'json') NOT NULL DEFAULT 'text',
  entry_value LONGTEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_portfolio_entries_key (entry_key)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS projects (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(180) NOT NULL,
  description TEXT NULL,
  status ENUM('published', 'draft', 'archived') NOT NULL DEFAULT 'draft',
  category ENUM('Web Systems', 'Research', 'UI/UX', 'Mobile') NOT NULL DEFAULT 'Web Systems',
  thumbnail_url VARCHAR(255) NULL,
  github_url VARCHAR(255) NULL,
  project_url VARCHAR(255) NULL,
  views BIGINT UNSIGNED NOT NULL DEFAULT 0,
  comments_count INT UNSIGNED NOT NULL DEFAULT 0,
  deleted_at DATETIME NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_projects_status (status),
  KEY idx_projects_category (category),
  KEY idx_projects_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS project_technologies (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id BIGINT UNSIGNED NOT NULL,
  technology VARCHAR(80) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pt_technology (technology),
  CONSTRAINT fk_pt_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contact_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(200) NOT NULL,
  category ENUM('Website Inquiry','Collaboration','Project Proposal','Job Opportunity','Other') NOT NULL DEFAULT 'Other',
  subject VARCHAR(255) NOT NULL DEFAULT '',
  message TEXT NOT NULL,
  status ENUM('unread','read','archived') NOT NULL DEFAULT 'unread',
  ip_address VARCHAR(45) NULL,
  deleted_at DATETIME NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cm_status (status),
  KEY idx_cm_category (category),
  KEY idx_cm_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS blog_posts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  excerpt TEXT NULL,
  content LONGTEXT NULL,
  category VARCHAR(80) NOT NULL DEFAULT 'General',
  thumbnail_url VARCHAR(255) NULL,
  status ENUM('published','draft','archived') NOT NULL DEFAULT 'draft',
  views BIGINT UNSIGNED NOT NULL DEFAULT 0,
  project_id BIGINT UNSIGNED NULL,
  published_at DATETIME NULL,
  deleted_at DATETIME NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_blog_posts_slug (slug),
  KEY idx_bp_status (status),
  KEY idx_bp_category (category),
  KEY idx_bp_created (created_at),
  KEY idx_bp_views (views),
  KEY idx_bp_project (project_id),
  CONSTRAINT fk_bp_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS awards (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  organization VARCHAR(255) NOT NULL,
  award_year YEAR NOT NULL,
  description TEXT NULL,
  image_url VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  deleted_at DATETIME NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_awards_year (award_year),
  KEY idx_awards_sort (sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS visitors (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  session_id VARCHAR(64) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  page VARCHAR(255) NOT NULL DEFAULT '/',
  referrer VARCHAR(255) NULL,
  duration_seconds INT UNSIGNED NULL,
  visited_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_v_session (session_id),
  KEY idx_v_visited (visited_at),
  KEY idx_v_page (page)
) ENGINE=InnoDB;

-- Run these ALTER statements on existing installations:
-- ALTER TABLE projects ADD COLUMN github_url VARCHAR(255) NULL AFTER thumbnail_url;
-- ALTER TABLE projects ADD COLUMN views BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER project_url;
-- ALTER TABLE projects ADD COLUMN comments_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER views;
-- ALTER TABLE projects MODIFY COLUMN category ENUM('Web Systems', 'Research', 'UI/UX', 'Mobile') NOT NULL DEFAULT 'Web Systems';
-- ALTER TABLE blog_posts ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER views;
-- ALTER TABLE blog_posts ADD CONSTRAINT fk_bp_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL;
-- ALTER TABLE projects ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER comments_count;
-- ALTER TABLE awards ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER sort_order;
-- ALTER TABLE blog_posts ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER published_at;
-- ALTER TABLE contact_messages ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER ip_address;

CREATE TABLE IF NOT EXISTS php_sessions (
  session_id  VARCHAR(128)  NOT NULL,
  session_data MEDIUMTEXT   NOT NULL,
  expires_at  DATETIME      NOT NULL,
  PRIMARY KEY (session_id),
  KEY idx_sessions_expires (expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS portfolio_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  category VARCHAR(50) NOT NULL,
  title VARCHAR(180) NOT NULL,
  subtitle VARCHAR(180) NULL,
  description TEXT NULL,
  metadata JSON NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_portfolio_items_category_sort (category, sort_order)
) ENGINE=InnoDB;
