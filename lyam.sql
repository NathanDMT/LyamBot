-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 04 juin 2025 à 07:45
-- Version du serveur : 9.1.0
-- Version de PHP : 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `lyam`
--

-- --------------------------------------------------------

--
-- Structure de la table `event_config`
--

DROP TABLE IF EXISTS `event_config`;
CREATE TABLE IF NOT EXISTS `event_config` (
  `server_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`server_id`,`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `modlog_config`
--

DROP TABLE IF EXISTS `modlog_config`;
CREATE TABLE IF NOT EXISTS `modlog_config` (
  `server_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `event_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'join',
  `channel_id` varchar(32) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`server_id`,`event_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `polls`
--

DROP TABLE IF EXISTS `polls`;
CREATE TABLE IF NOT EXISTS `polls` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message_id` varchar(50) NOT NULL,
  `channel_id` varchar(50) NOT NULL,
  `question` text NOT NULL,
  `fin_at` datetime NOT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sanctions`
--

DROP TABLE IF EXISTS `sanctions`;
CREATE TABLE IF NOT EXISTS `sanctions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(32) NOT NULL,
  `type` varchar(20) NOT NULL,
  `reason` text NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `moderator_id` varchar(32) NOT NULL,
  `server_id` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users_activity`
--

DROP TABLE IF EXISTS `users_activity`;
CREATE TABLE IF NOT EXISTS `users_activity` (
  `user_id` varchar(32) NOT NULL,
  `guild_id` varchar(32) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `xp` int NOT NULL DEFAULT '0',
  `level` int NOT NULL DEFAULT '0',
  `last_message_at` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`,`guild_id`),
  KEY `idx_user_guild` (`user_id`,`guild_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `warnings`
--

DROP TABLE IF EXISTS `warnings`;
CREATE TABLE IF NOT EXISTS `warnings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(32) NOT NULL,
  `warned_by` varchar(32) NOT NULL,
  `reason` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `server_id` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `xp_settings`
--

DROP TABLE IF EXISTS `xp_settings`;
CREATE TABLE IF NOT EXISTS `xp_settings` (
  `key` varchar(100) NOT NULL,
  `value` text,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `xp_settings`
--

INSERT INTO `xp_settings` (`key`, `value`) VALUES
('cooldown', '30'),
('ignored_channels', '[]'),
('levelup_channel_id', NULL),
('xp_max', '25'),
('xp_min', '5');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
