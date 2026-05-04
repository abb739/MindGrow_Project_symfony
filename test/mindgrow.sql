-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 01 mai 2026 à 00:16
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `mindgrow`
--

-- --------------------------------------------------------

--
-- Structure de la table `abonnement`
--

CREATE TABLE `abonnement` (
  `id_abonnement` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` longtext DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL,
  `duree_mois` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `abonnement`
--

INSERT INTO `abonnement` (`id_abonnement`, `nom`, `description`, `prix`, `duree_mois`) VALUES
(1, '33', 'EEE', 88.00, 1),
(2, 'xx', 'ddx', 177.00, 12),
(3, 'premium', 'dkjdjfjjf', 150.00, 6),
(4, 'Pack Premium', 'Accès illimité', 50.00, 12),
(5, 'dood', 'dddddd', 0.36, 6),
(6, 'good', 'Accès illimité', 700.00, 24),
(7, 'Pack Premium1', 'Accès illimité', 100.00, 6),
(8, 'badis', 'goods', 50.00, 1);

-- --------------------------------------------------------

--
-- Structure de la table `achat`
--

CREATE TABLE `achat` (
  `id_achat` int(11) NOT NULL,
  `id_abonnement` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `date_achat` datetime DEFAULT NULL,
  `statut` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `achat`
--

INSERT INTO `achat` (`id_achat`, `id_abonnement`, `id_utilisateur`, `date_achat`, `statut`) VALUES
(1, 1, 1, '2026-03-01 10:22:45', 'annulé'),
(2, 1, 1, '2026-03-01 10:41:06', 'annulé'),
(3, 2, 9, '2026-03-01 11:02:14', 'actif'),
(4, 2, 1, '2026-03-01 11:10:55', 'annulé'),
(5, 1, 1, '2026-03-01 11:16:21', 'annulé'),
(6, 1, 1, '2026-03-01 11:21:45', 'annulé'),
(7, 1, 1, '2026-03-01 11:24:46', 'annulé'),
(8, 1, 1, '2026-03-01 16:44:01', 'annulé'),
(9, 4, 1, '2026-04-07 03:18:39', 'annulé'),
(10, 2, 1, '2026-04-07 06:08:31', 'annulé'),
(11, 5, 1, '2026-04-07 14:00:34', 'annulé'),
(12, 2, 1, '2026-04-08 21:00:19', 'annulé'),
(13, 4, 1, '2026-04-08 22:54:40', 'annulé'),
(14, 2, 1, '2026-04-08 23:09:47', 'annulé'),
(15, 4, 12, '2026-04-10 00:12:50', 'actif'),
(16, 4, 1, '2026-04-10 00:26:53', 'actif'),
(17, 2, 13, '2026-04-10 00:30:29', 'annulé'),
(18, 6, 13, '2026-04-10 00:50:54', 'annulé'),
(19, 4, 13, '2026-04-10 00:53:50', 'annulé'),
(20, 2, 13, '2026-04-10 05:27:58', 'annulé'),
(21, 4, 13, '2026-04-10 05:45:37', 'annulé'),
(22, 4, 14, '2026-04-10 06:08:40', 'annulé'),
(23, 4, 13, '2026-04-10 06:51:45', 'annulé'),
(24, 2, 13, '2026-04-13 16:45:34', 'annulé'),
(25, 4, 13, '2026-04-13 17:15:08', 'annulé'),
(26, 2, 13, '2026-04-13 17:28:34', 'annulé'),
(27, 4, 13, '2026-04-13 17:55:18', 'annulé'),
(28, 2, 13, '2026-04-13 17:56:17', 'annulé'),
(29, 4, 13, '2026-04-13 17:59:13', 'annulé'),
(30, 2, 13, '2026-04-14 12:21:26', 'annulé'),
(31, 5, 13, '2026-04-15 23:33:12', 'annulé'),
(32, 2, 14, '2026-04-16 14:12:55', 'actif'),
(33, 4, 13, '2026-04-19 09:38:21', 'annulé'),
(34, 4, 13, '2026-04-19 09:50:23', 'annulé'),
(35, 4, 13, '2026-04-19 09:57:49', 'annulé'),
(36, 4, 13, '2026-04-19 10:12:23', 'actif'),
(37, 4, 27, '2026-04-21 13:39:23', 'actif');

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `id_avis` int(11) NOT NULL,
  `id_therapeute` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `note` int(11) DEFAULT NULL,
  `commentaire` longtext DEFAULT NULL,
  `date_avis` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `avis`
--

INSERT INTO `avis` (`id_avis`, `id_therapeute`, `id_utilisateur`, `note`, `commentaire`, `date_avis`) VALUES
(5, 3, 1, 5, 'good', '2026-04-07 06:00:20'),
(9, 6, 13, 4, 'not bad', '2026-04-14 11:10:39'),
(10, 6, 27, 1, 'bad', '2026-04-21 13:45:28');

-- --------------------------------------------------------

--
-- Structure de la table `categorie`
--

CREATE TABLE `categorie` (
  `id_categorie` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categorie`
--

INSERT INTO `categorie` (`id_categorie`, `nom`, `description`) VALUES
(4, 'xxlarge', ''),
(5, 'doctor', ''),
(6, 'meddeb', ''),
(7, 'yoga', 'exercice yoga ');

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `favori_programme`
--

CREATE TABLE `favori_programme` (
  `id_favori` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `id_programme` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `favori_programme`
--

INSERT INTO `favori_programme` (`id_favori`, `id_utilisateur`, `id_programme`, `created_at`) VALUES
(1, 27, 6, '2026-04-27 22:27:01');

-- --------------------------------------------------------

--
-- Structure de la table `programme`
--

CREATE TABLE `programme` (
  `id_programme` int(11) NOT NULL,
  `id_categorie` int(11) NOT NULL,
  `titre` varchar(100) NOT NULL,
  `description` longtext DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `video` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `programme`
--

INSERT INTO `programme` (`id_programme`, `id_categorie`, `titre`, `description`, `image`, `video`) VALUES
(3, 4, 'ffg', '', 'uploads/programmes/images/prog_69d47dfb6eeb27.59736148.jpg', 'uploads/programmes/videos/prog_69d482f57a3ec0.13810078.mp4'),
(6, 5, 'debba', 'good', 'uploads/programmes/images/prog_69d482b7606f21.08430957.jpg', 'uploads/programmes/videos/prog_69d482b760dd60.02938206.mp4'),
(8, 7, 'yoga', 'exrecice de resp', 'uploads/programmes/images/prog_69d4eebfd9fbe3.17941553.jpeg', 'uploads/programmes/videos/prog_69d4eebfda3073.93706633.mp4'),
(9, 7, 'eee', 'goods', 'uploads/programmes/images/prog_69d6a55c422cd5.69974670.jpeg', 'uploads/programmes/videos/prog_69d6a55c429040.38458471.mp4');

-- --------------------------------------------------------

--
-- Structure de la table `reservation`
--

CREATE TABLE `reservation` (
  `id_reservation` int(11) NOT NULL,
  `id_seance` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `date_reservation` datetime DEFAULT NULL,
  `statut` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reservation`
--

INSERT INTO `reservation` (`id_reservation`, `id_seance`, `id_utilisateur`, `date_reservation`, `statut`) VALUES
(5, 2, 1, '2026-03-01 04:40:02', 'annulée'),
(6, 1, 1, '2026-03-01 16:28:47', 'confirmée'),
(7, 2, 1, '2026-03-01 17:55:51', 'confirmée'),
(8, 1, 1, '2026-04-07 03:18:47', 'confirmée'),
(9, 3, 1, '2026-04-07 03:18:57', 'en attente'),
(10, 2, 1, '2026-04-07 13:24:17', 'confirmée'),
(11, 1, 11, '2026-04-07 13:35:21', 'en attente'),
(12, 2, 11, '2026-04-07 13:35:26', 'confirmée'),
(13, 5, 11, '2026-04-07 13:36:07', 'en attente'),
(14, 2, 1, '2026-04-08 23:07:14', 'confirmée'),
(15, 1, 12, '2026-04-08 23:29:46', 'en attente'),
(16, 1, 13, '2026-04-11 06:18:34', 'annulée'),
(17, 2, 13, '2026-04-15 23:34:11', 'annulée'),
(18, 7, 13, '2026-04-15 23:34:45', 'annulée'),
(19, 6, 13, '2026-04-15 23:35:24', 'annulée'),
(20, 5, 13, '2026-04-15 23:36:46', 'confirmée'),
(21, 8, 13, '2026-04-15 23:45:53', 'en attente'),
(22, 3, 13, '2026-04-15 23:46:31', 'en attente'),
(23, 9, 13, '2026-04-15 23:58:25', 'en attente'),
(24, 6, 14, '2026-04-16 14:09:13', 'en attente'),
(25, 2, 13, '2026-04-17 08:20:04', 'confirmée'),
(26, 7, 27, '2026-04-21 13:56:19', 'en attente');

-- --------------------------------------------------------

--
-- Structure de la table `seance`
--

CREATE TABLE `seance` (
  `id_seance` int(11) NOT NULL,
  `titre` varchar(100) NOT NULL,
  `description` longtext DEFAULT NULL,
  `lieu` varchar(150) NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `capacite` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `seance`
--

INSERT INTO `seance` (`id_seance`, `titre`, `description`, `lieu`, `date_debut`, `date_fin`, `capacite`, `image`) VALUES
(1, 'eee', 'dddddd', 'Avenue des Sanhajites, Sanhaja, Délégation Oued Ellil, Gouvernorat La Manouba, 2021, Tunisie', '2028-01-19 03:15:00', '2028-02-09 07:35:00', 15, 'C:\\Users\\21658\\OneDrive\\Images\\wallpaperflare.com_wallpaper.jpg'),
(2, 'YYE', 'DDDDDDD', 'Utique, Délégation Utique, Gouvernorat Bizerte, 7060, Tunisie', '2026-02-14 16:40:00', '2026-02-28 00:25:00', 10, NULL),
(3, 'ss', 'ssss', 'Borj Touil, Délégation Raoued, Gouvernorat Ariana, 2081, Tunisie', '2026-03-25 02:30:00', '2026-04-04 04:40:00', 150, 'C:\\Users\\21658\\OneDrive\\Images\\wallpaperflare.com_wallpaper (1).jpg'),
(5, 'meditation', 'seance new', 'tunis', '2026-04-13 12:35:00', '2026-04-14 13:35:00', 7, NULL),
(6, 'debba', 'goods', 'Avenue des Sanhajites, Sanhaja, Délégation Oued Ellil, Gouvernorat La Manouba, 2021, Tunisie', '2026-04-22 19:56:00', '2026-04-30 19:57:00', 33, NULL),
(7, 'eee', 'Accès illimité', 'tunis', '2027-05-07 20:17:00', '2027-05-31 20:17:00', 40, '69d6a9fd9b4d5.jpg'),
(8, 'meditation1', 'ggoogg', 'tunis', '2030-01-01 20:24:00', '2030-01-31 20:24:00', 30, '69d6aba90ed0f.jpg'),
(9, 'fffd', 'goods', 'Rue Abd Allah Ben Ammeur, Franceville, El Omrane, Délégation El Omrane, Tunis, Gouvernorat Tunis, 1005, Tunisie', '2031-01-01 22:55:00', '2031-03-01 22:55:00', 20, '69e009e7deb59.png'),
(10, 'hgjl', 'fghjqsdfghj', 'Rue Amor Jammali, Bardo Nord, Le Bardo, Tunis, Gouvernorat Tunis, 2000, Tunisie', '2026-05-07 12:59:00', '2026-07-11 13:00:00', 18, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `therapeute`
--

CREATE TABLE `therapeute` (
  `id_therapeute` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `certificat` varchar(255) DEFAULT NULL,
  `specialite` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `date_inscription` datetime DEFAULT NULL,
  `statut_certificat` varchar(20) DEFAULT NULL,
  `certificat_texte` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `therapeute`
--

INSERT INTO `therapeute` (`id_therapeute`, `nom`, `prenom`, `image`, `certificat`, `specialite`, `email`, `telephone`, `date_inscription`, `statut_certificat`, `certificat_texte`) VALUES
(3, 'badis', 'ghrab', '/uploads/therapeutes/therapeute_69d4bfe50f66e.jpg', NULL, 'gggg', 'badisghrab11@gmail.com', '58140404', '2026-04-07 03:53:37', NULL, NULL),
(6, 'BEN', 'ayoub', '/uploads/therapeutes/therapeute_69d4e79ab20ad.jpg', NULL, 'psy', 'ayob@gmail.com', '12345676', '2026-04-07 13:16:42', NULL, NULL),
(7, 'aziz', 'ghrab', '/uploads/therapeutes/therapeute_69e764c5bab7e.jpg', '/uploads/certificats/cert_69e764c5bb258.jpg', 'psyco', 'badisghrab10@gmail.com', '50054053', '2026-04-21 13:51:33', 'verifie', '');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id_utilisateur` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `date_inscription` datetime DEFAULT NULL,
  `role` varchar(20) NOT NULL,
  `theme_preference` varchar(10) DEFAULT 'auto',
  `is_verified` tinyint(4) NOT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id_utilisateur`, `nom`, `prenom`, `email`, `mot_de_passe`, `date_inscription`, `role`, `theme_preference`, `is_verified`, `verification_token`, `reset_token`, `reset_token_expires_at`) VALUES
(23, 'maram', 'khalloufi', 'maram.khalloufi@esprit.tn', '$2y$10$ZpSvdUm808YcLhc1tmm1TuBGceWuIXKunXZ/VNrzvRnsbz1YyS9su', '2026-04-20 13:47:18', 'client', 'auto', 0, 'f63407570ba70ab70c8486364a4aea9279bf1298b7d0785bed466009c8c61e04', NULL, NULL),
(24, 'Admin', 'MindGrow', 'adminmindrow@gmail.com', '$2y$10$cNNYnDOps683nrEDOGqQ7OLFLQo.cSrGB7bcFmiJfKr4dCa3GQYHO', '2026-04-20 16:30:18', 'admin', 'auto', 1, NULL, NULL, NULL),
(27, 'badis', 'ghrab', 'badisghrab9@gmail.com', '$2y$10$5EYRIBQz2xfjsNr.1I8vWexcZt1IFIV0r738p3OJNJ1dDfOH0RnqG', '2026-04-20 19:59:03', 'client', 'dark', 1, NULL, NULL, NULL),
(28, 'maram', 'khalloufi', 'badisghrab10@gmail.com', '$2y$10$RS8IhxSzR7noHbtcHy2JEeeLLovif7TtJWEpclqBNfpafdKREXGF6', '2026-04-21 13:36:13', 'client', 'auto', 1, NULL, NULL, NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `abonnement`
--
ALTER TABLE `abonnement`
  ADD PRIMARY KEY (`id_abonnement`);

--
-- Index pour la table `achat`
--
ALTER TABLE `achat`
  ADD PRIMARY KEY (`id_achat`);

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id_avis`);

--
-- Index pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`id_categorie`);

--
-- Index pour la table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Index pour la table `favori_programme`
--
ALTER TABLE `favori_programme`
  ADD PRIMARY KEY (`id_favori`),
  ADD UNIQUE KEY `unique_favori` (`id_utilisateur`,`id_programme`),
  ADD KEY `fk_favori_programme` (`id_programme`);

--
-- Index pour la table `programme`
--
ALTER TABLE `programme`
  ADD PRIMARY KEY (`id_programme`);

--
-- Index pour la table `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`id_reservation`);

--
-- Index pour la table `seance`
--
ALTER TABLE `seance`
  ADD PRIMARY KEY (`id_seance`);

--
-- Index pour la table `therapeute`
--
ALTER TABLE `therapeute`
  ADD PRIMARY KEY (`id_therapeute`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `UNIQ_1D1C63B3E7927C74` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `abonnement`
--
ALTER TABLE `abonnement`
  MODIFY `id_abonnement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `achat`
--
ALTER TABLE `achat`
  MODIFY `id_achat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `id_avis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `id_categorie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `favori_programme`
--
ALTER TABLE `favori_programme`
  MODIFY `id_favori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `programme`
--
ALTER TABLE `programme`
  MODIFY `id_programme` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `reservation`
--
ALTER TABLE `reservation`
  MODIFY `id_reservation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT pour la table `seance`
--
ALTER TABLE `seance`
  MODIFY `id_seance` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `therapeute`
--
ALTER TABLE `therapeute`
  MODIFY `id_therapeute` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `favori_programme`
--
ALTER TABLE `favori_programme`
  ADD CONSTRAINT `fk_favori_programme` FOREIGN KEY (`id_programme`) REFERENCES `programme` (`id_programme`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_favori_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
