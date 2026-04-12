-- ===========================================================
-- Migration: Add Mood and Goal Tables
-- Created: 2026-04-09 12:00:00
-- Description: Add Mood and Goal tables for mood tracking 
--              and goal management modules
-- ===========================================================

-- Create mood table
CREATE TABLE IF NOT EXISTS `mood` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `feeling` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_mood_user_id` (`user_id`),
  CONSTRAINT `FK_mood_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create goal table
CREATE TABLE IF NOT EXISTS `goal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `deadline` datetime NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_goal_user_id` (`user_id`),
  CONSTRAINT `FK_goal_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mark migration as executed
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) 
VALUES ('DoctrineMigrations\\Version20260409120000', NOW(), 100)
ON DUPLICATE KEY UPDATE `executed_at` = NOW();
