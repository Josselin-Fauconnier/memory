-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : mar. 14 oct. 2025 à 07:44
-- Version du serveur : 8.4.3
-- Version de PHP : 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `memory_game`
--

-- --------------------------------------------------------

--
-- Structure de la table `games`
--

CREATE TABLE `games` (
  `id` int NOT NULL,
  `player_id` int NOT NULL,
  `pairs_count` int NOT NULL,
  `moves_count` int NOT NULL,
  `time_seconds` int NOT NULL,
  `completed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `leaderboard`
--

CREATE TABLE `leaderboard` (
  `id` int NOT NULL,
  `player_id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pairs_count` int NOT NULL,
  `moves_count` int NOT NULL,
  `time_seconds` int NOT NULL,
  `score` decimal(8,2) NOT NULL,
  `achieved_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `players`
--

CREATE TABLE `players` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `player_stats`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `player_stats` (
`id` int
,`username` varchar(50)
,`total_games` bigint
,`avg_moves` decimal(14,4)
,`best_time` int
,`personal_best` decimal(8,2)
,`member_since` timestamp
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `top_10_players`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `top_10_players` (
`username` varchar(50)
,`pairs_count` int
,`moves_count` int
,`time_seconds` int
,`score` decimal(8,2)
,`achieved_at` timestamp
,`rank_position` bigint unsigned
);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_player_games` (`player_id`,`completed_at` DESC);

--
-- Index pour la table `leaderboard`
--
ALTER TABLE `leaderboard`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player_id` (`player_id`),
  ADD KEY `idx_ranking` (`score` DESC,`achieved_at`);

--
-- Index pour la table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `games`
--
ALTER TABLE `games`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `leaderboard`
--
ALTER TABLE `leaderboard`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `players`
--
ALTER TABLE `players`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure de la vue `player_stats`
--
DROP TABLE IF EXISTS `player_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `player_stats`  AS SELECT `p`.`id` AS `id`, `p`.`username` AS `username`, count(`g`.`id`) AS `total_games`, avg(`g`.`moves_count`) AS `avg_moves`, min(`g`.`time_seconds`) AS `best_time`, max(`l`.`score`) AS `personal_best`, `p`.`created_at` AS `member_since` FROM ((`players` `p` left join `games` `g` on((`p`.`id` = `g`.`player_id`))) left join `leaderboard` `l` on((`p`.`id` = `l`.`player_id`))) GROUP BY `p`.`id`, `p`.`username`, `p`.`created_at` ;

-- --------------------------------------------------------

--
-- Structure de la vue `top_10_players`
--
DROP TABLE IF EXISTS `top_10_players`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `top_10_players`  AS SELECT `leaderboard`.`username` AS `username`, `leaderboard`.`pairs_count` AS `pairs_count`, `leaderboard`.`moves_count` AS `moves_count`, `leaderboard`.`time_seconds` AS `time_seconds`, `leaderboard`.`score` AS `score`, `leaderboard`.`achieved_at` AS `achieved_at`, row_number() OVER (ORDER BY `leaderboard`.`score` desc,`leaderboard`.`achieved_at` ) AS `rank_position` FROM `leaderboard` ORDER BY `leaderboard`.`score` DESC, `leaderboard`.`achieved_at` ASC LIMIT 0, 10 ;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `games`
--
ALTER TABLE `games`
  ADD CONSTRAINT `games_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `leaderboard`
--
ALTER TABLE `leaderboard`
  ADD CONSTRAINT `leaderboard_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
