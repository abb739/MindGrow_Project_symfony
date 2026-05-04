<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260419073019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE favoris DROP FOREIGN KEY `favoris_ibfk_1`');
        $this->addSql('ALTER TABLE favoris DROP FOREIGN KEY `favoris_ibfk_2`');
        $this->addSql('DROP TABLE favoris');
        $this->addSql('ALTER TABLE abonnement CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE achat DROP FOREIGN KEY `achat_ibfk_1`');
        $this->addSql('ALTER TABLE achat DROP FOREIGN KEY `achat_ibfk_2`');
        $this->addSql('DROP INDEX id_abonnement ON achat');
        $this->addSql('DROP INDEX id_utilisateur ON achat');
        $this->addSql('ALTER TABLE achat CHANGE date_achat date_achat DATETIME DEFAULT NULL, CHANGE statut statut VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY `avis_ibfk_1`');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY `avis_ibfk_2`');
        $this->addSql('DROP INDEX id_utilisateur ON avis');
        $this->addSql('DROP INDEX id_therapeute ON avis');
        $this->addSql('ALTER TABLE avis CHANGE commentaire commentaire LONGTEXT DEFAULT NULL, CHANGE date_avis date_avis DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE categorie CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE programme DROP FOREIGN KEY `programme_ibfk_1`');
        $this->addSql('DROP INDEX id_categorie ON programme');
        $this->addSql('ALTER TABLE programme CHANGE description description LONGTEXT DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE video video VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY `reservation_ibfk_1`');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY `reservation_ibfk_2`');
        $this->addSql('DROP INDEX id_seance ON reservation');
        $this->addSql('DROP INDEX id_utilisateur ON reservation');
        $this->addSql('ALTER TABLE reservation CHANGE date_reservation date_reservation DATETIME DEFAULT NULL, CHANGE statut statut VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE seance CHANGE description description LONGTEXT DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX email ON therapeute');
        $this->addSql('ALTER TABLE therapeute ADD statut_certificat VARCHAR(20) DEFAULT NULL, ADD certificat_texte LONGTEXT DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE certificat certificat VARCHAR(255) DEFAULT NULL, CHANGE specialite specialite VARCHAR(100) DEFAULT NULL, CHANGE email email VARCHAR(150) DEFAULT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE date_inscription date_inscription DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE date_inscription date_inscription DATETIME DEFAULT NULL, CHANGE role role VARCHAR(20) NOT NULL, CHANGE theme_preference theme_preference VARCHAR(10) DEFAULT \'auto\'');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX email TO UNIQ_1D1C63B3E7927C74');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE favoris (id_favori INT AUTO_INCREMENT NOT NULL, id_utilisateur INT NOT NULL, id_programme INT NOT NULL, UNIQUE INDEX id_utilisateur (id_utilisateur, id_programme), INDEX id_programme (id_programme), INDEX IDX_8933C43250EAE44 (id_utilisateur), PRIMARY KEY (id_favori)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE favoris ADD CONSTRAINT `favoris_ibfk_1` FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favoris ADD CONSTRAINT `favoris_ibfk_2` FOREIGN KEY (id_programme) REFERENCES programme (id_programme) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE abonnement CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE achat CHANGE date_achat date_achat DATETIME DEFAULT \'NULL\', CHANGE statut statut ENUM(\'actif\', \'expiré\', \'annulé\') DEFAULT \'\'\'actif\'\'\'');
        $this->addSql('ALTER TABLE achat ADD CONSTRAINT `achat_ibfk_1` FOREIGN KEY (id_abonnement) REFERENCES abonnement (id_abonnement) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE achat ADD CONSTRAINT `achat_ibfk_2` FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX id_abonnement ON achat (id_abonnement)');
        $this->addSql('CREATE INDEX id_utilisateur ON achat (id_utilisateur)');
        $this->addSql('ALTER TABLE avis CHANGE commentaire commentaire TEXT DEFAULT NULL, CHANGE date_avis date_avis DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT `avis_ibfk_1` FOREIGN KEY (id_therapeute) REFERENCES therapeute (id_therapeute) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT `avis_ibfk_2` FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX id_utilisateur ON avis (id_utilisateur)');
        $this->addSql('CREATE INDEX id_therapeute ON avis (id_therapeute)');
        $this->addSql('ALTER TABLE categorie CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE programme CHANGE description description TEXT DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE video video VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE programme ADD CONSTRAINT `programme_ibfk_1` FOREIGN KEY (id_categorie) REFERENCES categorie (id_categorie) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX id_categorie ON programme (id_categorie)');
        $this->addSql('ALTER TABLE reservation CHANGE date_reservation date_reservation DATETIME DEFAULT \'NULL\', CHANGE statut statut ENUM(\'confirmée\', \'annulée\', \'en attente\') DEFAULT \'\'\'en attente\'\'\'');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT `reservation_ibfk_1` FOREIGN KEY (id_seance) REFERENCES seance (id_seance) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT `reservation_ibfk_2` FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX id_seance ON reservation (id_seance)');
        $this->addSql('CREATE INDEX id_utilisateur ON reservation (id_utilisateur)');
        $this->addSql('ALTER TABLE seance CHANGE description description TEXT DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE therapeute DROP statut_certificat, DROP certificat_texte, CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE certificat certificat VARCHAR(255) DEFAULT \'NULL\', CHANGE specialite specialite VARCHAR(100) DEFAULT \'NULL\', CHANGE email email VARCHAR(150) DEFAULT \'NULL\', CHANGE telephone telephone VARCHAR(20) DEFAULT \'NULL\', CHANGE date_inscription date_inscription DATETIME DEFAULT \'NULL\'');
        $this->addSql('CREATE UNIQUE INDEX email ON therapeute (email)');
        $this->addSql('ALTER TABLE utilisateur CHANGE role role ENUM(\'admin\', \'client\') NOT NULL, CHANGE date_inscription date_inscription DATETIME DEFAULT \'NULL\', CHANGE theme_preference theme_preference VARCHAR(10) DEFAULT \'\'\'auto\'\'\'');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX uniq_1d1c63b3e7927c74 TO email');
    }
}
