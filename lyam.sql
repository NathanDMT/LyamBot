-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 02 juin 2025 à 20:18
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

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
  `server_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`server_id`,`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `event_config`
--

INSERT INTO `event_config` (`server_id`, `event_type`, `channel_id`) VALUES
('455353823670829057', 'boost', '455597870196719617'),
('455353823670829057', 'join', '455597870196719617'),
('455353823670829057', 'leave', '455597870196719617');

-- --------------------------------------------------------

--
-- Structure de la table `modlog_config`
--

DROP TABLE IF EXISTS `modlog_config`;
CREATE TABLE IF NOT EXISTS `modlog_config` (
  `server_id` varchar(32) NOT NULL,
  `channel_id` varchar(32) NOT NULL,
  PRIMARY KEY (`server_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `modlog_config`
--

INSERT INTO `modlog_config` (`server_id`, `channel_id`) VALUES
('455353823670829057', '455597965365608478');

-- --------------------------------------------------------

--
-- Structure de la table `mutes`
--

DROP TABLE IF EXISTS `mutes`;
CREATE TABLE IF NOT EXISTS `mutes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `muted_by` varchar(50) NOT NULL,
  `reason` text NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `server_id` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `sanctions`
--

INSERT INTO `sanctions` (`id`, `user_id`, `type`, `reason`, `date`, `moderator_id`, `server_id`) VALUES
(1, '892507588099440640', 'mute', 'Aucune raison fournie', '2025-06-02 20:47:22', '248706764197986304', '455353823670829057'),
(2, '892507588099440640', 'mute', 'test', '2025-06-02 20:53:59', '248706764197986304', '455353823670829057'),
(3, '892507588099440640', 'mute', 'test', '2025-06-02 20:54:17', '248706764197986304', '455353823670829057');

-- --------------------------------------------------------

--
-- Structure de la table `users_activity`
--

DROP TABLE IF EXISTS `users_activity`;
CREATE TABLE IF NOT EXISTS `users_activity` (
  `user_id` varchar(30) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `xp` int DEFAULT '0',
  `level` int DEFAULT '1',
  `last_message_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `levelup_notify` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `users_activity`
--

INSERT INTO `users_activity` (`user_id`, `username`, `xp`, `level`, `last_message_at`, `levelup_notify`) VALUES
('248706764197986304', 'n4at', 79, 0, '2025-05-28 19:28:03', 1);

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `warnings`
--

INSERT INTO `warnings` (`id`, `user_id`, `warned_by`, `reason`, `created_at`, `server_id`) VALUES
(1, '892507588099440640', '248706764197986304', 'test', '2025-05-28 12:34:59', '455353823670829057'),
(2, '892507588099440640', '248706764197986304', 'test1', '2025-05-28 12:42:07', '455353823670829057'),
(3, '892507588099440640', '248706764197986304', 'test3', '2025-05-28 12:42:11', '455353823670829057'),
(4, '892507588099440640', '248706764197986304', 'test2', '2025-05-28 12:42:19', '455353823670829057'),
(5, '892507588099440640', '248706764197986304', 'test5', '2025-05-28 12:44:57', '455353823670829057'),
(6, '892507588099440640', '248706764197986304', 'test6', '2025-05-28 12:45:02', '455353823670829057');

-- --------------------------------------------------------

--
-- Structure de la table `xp_settings`
--

DROP TABLE IF EXISTS `xp_settings`;
CREATE TABLE IF NOT EXISTS `xp_settings` (
  `key` varchar(50) NOT NULL,
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
('xp_max', '20'),
('xp_min', '5');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
