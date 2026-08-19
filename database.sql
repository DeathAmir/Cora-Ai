CREATE DATABASE IF NOT EXISTS coradb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE coradb;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  avatar_seed VARCHAR(64) NOT NULL,
  email_verified TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS conversations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(120) NOT NULL DEFAULT 'گفتگوی جدید',
  mode ENUM('chat','image') NOT NULL DEFAULT 'chat',
  model VARCHAR(120) NULL,
  pinned TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_conversations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_conversations_user_updated (user_id, updated_at),
  INDEX idx_conversations_deleted (deleted_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id BIGINT UNSIGNED NOT NULL,
  role ENUM('user','assistant','system') NOT NULL,
  kind ENUM('text','image','error') NOT NULL DEFAULT 'text',
  content MEDIUMTEXT NOT NULL,
  meta_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_messages_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  INDEX idx_messages_conversation (conversation_id, id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rate_limits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bucket_key CHAR(64) NOT NULL,
  action_name VARCHAR(40) NOT NULL,
  hits INT UNSIGNED NOT NULL DEFAULT 0,
  window_start DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_rate_bucket (bucket_key, action_name),
  INDEX idx_rate_window (window_start)
) ENGINE=InnoDB;
