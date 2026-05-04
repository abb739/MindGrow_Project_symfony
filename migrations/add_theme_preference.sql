-- Migration : Ajout de la colonne theme_preference à la table utilisateur
-- Exécutez ce fichier dans phpMyAdmin ou via MySQL

ALTER TABLE `utilisateur` 
ADD COLUMN IF NOT EXISTS `theme_preference` VARCHAR(10) NULL DEFAULT 'auto' 
COMMENT 'Préférence de thème : light, dark, auto';

-- Vérification
SELECT 'Migration theme_preference appliquée avec succès' AS status;
