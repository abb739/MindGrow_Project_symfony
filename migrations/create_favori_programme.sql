-- ============================================================
-- Migration : Création de la table favori_programme
-- Projet    : MindGrow — Module Programme Avancé
-- ============================================================

CREATE TABLE IF NOT EXISTS `favori_programme` (
    `id_favori`       INT NOT NULL AUTO_INCREMENT,
    `id_utilisateur`  INT NOT NULL,
    `id_programme`    INT NOT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_favori`),
    UNIQUE KEY `unique_favori` (`id_utilisateur`, `id_programme`),

    CONSTRAINT `fk_favori_utilisateur`
        FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `fk_favori_programme`
        FOREIGN KEY (`id_programme`) REFERENCES `programme` (`id_programme`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
