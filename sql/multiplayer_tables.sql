-- Multiplayer System Tables

-- Player Messages
CREATE TABLE IF NOT EXISTS `player_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_content` text NOT NULL,
  `message_type` enum('chat','system','event') NOT NULL DEFAULT 'chat',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  KEY `is_read` (`is_read`),
  CONSTRAINT `player_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `player_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Real-time Notifications
CREATE TABLE IF NOT EXISTS `real_time_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `character_id` int(11) NOT NULL,
  `notification_data` json NOT NULL,
  `is_delivered` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `character_id` (`character_id`),
  KEY `is_delivered` (`is_delivered`),
  CONSTRAINT `real_time_notifications_ibfk_1` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Server Nodes for Load Balancing
CREATE TABLE IF NOT EXISTS `server_nodes` (
  `id` varchar(36) NOT NULL,
  `server_url` varchar(255) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 100,
  `active_users` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive','maintenance') NOT NULL DEFAULT 'active',
  `last_heartbeat` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Performance Logs
CREATE TABLE IF NOT EXISTS `performance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `query_time` float NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `user_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- User Sessions for Multi-session Management
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `session_data` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `session_id` (`session_id`),
  KEY `last_activity` (`last_activity`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Multiplayer Activities
CREATE TABLE IF NOT EXISTS `multiplayer_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `activity_type` enum('quest','dungeon','raid','trade','crafting','exploration') NOT NULL,
  `min_level` int(11) NOT NULL DEFAULT 1,
  `max_players` int(11) NOT NULL DEFAULT 4,
  `creator_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `status` enum('recruiting','in_progress','completed','cancelled') NOT NULL DEFAULT 'recruiting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `creator_id` (`creator_id`),
  KEY `location_id` (`location_id`),
  KEY `status` (`status`),
  CONSTRAINT `multiplayer_activities_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `multiplayer_activities_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Multiplayer Activity Participants
CREATE TABLE IF NOT EXISTS `multiplayer_activity_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) NOT NULL,
  `character_id` int(11) NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `status` enum('invited','joined','left','kicked') NOT NULL DEFAULT 'joined',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `activity_character` (`activity_id`,`character_id`),
  KEY `character_id` (`character_id`),
  CONSTRAINT `multiplayer_activity_participants_ibfk_1` FOREIGN KEY (`activity_id`) REFERENCES `multiplayer_activities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `multiplayer_activity_participants_ibfk_2` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Game Settings Table
CREATE TABLE IF NOT EXISTS `game_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `setting_description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_name` (`setting_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert Default Game Settings
INSERT INTO `game_settings` (`setting_name`, `setting_value`, `setting_description`) VALUES
('load_balance_threshold', '200', 'Number of active users before load balancing kicks in'),
('max_sessions_per_user', '3', 'Maximum number of simultaneous sessions per user'),
('message_rate_limit', '10', 'Maximum number of messages a user can send per minute'),
('multiplayer_enabled', 'true', 'Whether multiplayer features are enabled'),
('notification_check_interval', '10', 'Seconds between notification checks (client-side)');

-- Add session_id column to users table if it doesn't exist
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `session_id` varchar(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `last_activity` timestamp NULL DEFAULT NULL; 