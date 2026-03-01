-- Querro Database Installation Script
-- Run this file to set up the database schema
-- Usage: mysql -u root -p querro < install.sql

-- Ensure we're using UTF-8mb4
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Create account table
CREATE TABLE IF NOT EXISTS `account` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `active` SMALLINT NOT NULL DEFAULT 1,
  PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;

-- Create user table (with account_id from ALTER TABLE migration)
CREATE TABLE IF NOT EXISTS `user` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `email` VARCHAR(180) NOT NULL,
  `roles` JSON NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `account_id` INT NOT NULL,
  `username` VARCHAR(255) NOT NULL,
  `active` SMALLINT NOT NULL DEFAULT 1,
  UNIQUE INDEX `UNIQ_8D93D649E7927C74` (`email`),
  PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;

-- Create dbs table (external database connections)
CREATE TABLE IF NOT EXISTS `dbs` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `account_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `server` VARCHAR(255) NOT NULL,
  `username` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `port` VARCHAR(255) NOT NULL DEFAULT '3306',
  `type` VARCHAR(16) NOT NULL DEFAULT 'mysql',
  `encoding` VARCHAR(128) NOT NULL DEFAULT 'utf8mb4',
  `active` SMALLINT NOT NULL DEFAULT 1,
  `tables` LONGTEXT DEFAULT NULL,
  PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;

-- Create query table (saved queries)
CREATE TABLE IF NOT EXISTS `query` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `user_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `db` VARCHAR(255) NOT NULL,
  `query` LONGTEXT NOT NULL,
  `share_key` VARCHAR(50) DEFAULT NULL,
  `share_pass` VARCHAR(20) DEFAULT NULL,
  `date_created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;

-- Create column_prop table (column formatting preferences)
CREATE TABLE IF NOT EXISTS `column_prop` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `query_id` INT DEFAULT NULL,
  `db` VARCHAR(255) NOT NULL,
  `db_table` VARCHAR(255) NOT NULL,
  `metatype` VARCHAR(2) DEFAULT NULL,
  `col_name` VARCHAR(255) NOT NULL,
  `hidden` SMALLINT DEFAULT NULL,
  `readonly` SMALLINT DEFAULT NULL,
  `required` SMALLINT DEFAULT NULL,
  `wysiwyg` SMALLINT DEFAULT NULL,
  `title` VARCHAR(255) DEFAULT NULL,
  `align` VARCHAR(16) DEFAULT NULL,
  `width` VARCHAR(255) DEFAULT NULL,
  `linkto` VARCHAR(255) DEFAULT NULL,
  `edittype` VARCHAR(255) DEFAULT NULL,
  `conditional_format` VARCHAR(255) DEFAULT NULL,
  `customrule` VARCHAR(255) DEFAULT NULL,
  `default_value` VARCHAR(255) DEFAULT NULL,
  `format` VARCHAR(255) DEFAULT NULL,
  `hyperlink` VARCHAR(255) DEFAULT NULL,
  `property` VARCHAR(255) DEFAULT NULL,
  `img` VARCHAR(255) DEFAULT NULL,
  `file_upload` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;

-- Create messenger_messages table (Symfony async messaging)
CREATE TABLE IF NOT EXISTS `messenger_messages` (
  `id` BIGINT AUTO_INCREMENT NOT NULL,
  `body` LONGTEXT NOT NULL,
  `headers` LONGTEXT NOT NULL,
  `queue_name` VARCHAR(190) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `available_at` DATETIME NOT NULL,
  `delivered_at` DATETIME DEFAULT NULL,
  INDEX `IDX_75EA56E0FB7336F0` (`queue_name`),
  INDEX `IDX_75EA56E0E3BD61CE` (`available_at`),
  INDEX `IDX_75EA56E016BA31DB` (`delivered_at`),
  PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;

-- Create ai_settings table (AI/OpenAI configuration per account)
CREATE TABLE IF NOT EXISTS `ai_settings` (
  `account_id` INT NOT NULL,
  `openai_api_key` VARCHAR(255) DEFAULT NULL,
  `openai_model` VARCHAR(255) DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY(`account_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;

-- Create ai_query_log table (audit log for AI-generated SQL queries)
CREATE TABLE IF NOT EXISTS `ai_query_log` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `account_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `database_id` VARCHAR(255) DEFAULT NULL,
  `natural_language_input` LONGTEXT DEFAULT NULL,
  `generated_sql` LONGTEXT DEFAULT NULL,
  `model_used` VARCHAR(255) DEFAULT NULL,
  `tokens_used` INT DEFAULT NULL,
  `execution_time_ms` INT DEFAULT NULL,
  `success` TINYINT(1) DEFAULT NULL,
  `error_message` LONGTEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ai_query_log_account` (`account_id`),
  INDEX `idx_ai_query_log_created` (`created_at`),
  PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;

-- Create doctrine_migration_versions table (Doctrine migrations tracking)
CREATE TABLE IF NOT EXISTS `doctrine_migration_versions` (
  `version` VARCHAR(191) NOT NULL,
  `executed_at` DATETIME DEFAULT NULL,
  `execution_time` INT DEFAULT NULL,
  PRIMARY KEY(`version`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;

-- Performance Indexes (from LOGIN_PERFORMANCE_OPTIMIZATIONS.md)
-- Note: user.email already has UNIQUE index from table creation
CREATE INDEX `idx_dbs_account_id` ON `dbs` (`account_id`);
CREATE INDEX `idx_dbs_account_active` ON `dbs` (`account_id`, `active`);

-- Create default account
INSERT INTO `account` (`id`, `name`, `active`) VALUES (1, 'Default Account', 1);

-- Installation complete
SELECT 'Querro database schema installed successfully!' AS '';
