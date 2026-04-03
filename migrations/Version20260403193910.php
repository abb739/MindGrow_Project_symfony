<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260403193910 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE achat CHANGE statut statut ENUM(\'actif\',\'expiré\',\'annulé\')');
        $this->addSql('DROP INDEX id_utilisateur ON favoris');
        $this->addSql('ALTER TABLE favoris DROP FOREIGN KEY `favoris_ibfk_2`');
        $this->addSql('DROP INDEX id_programme ON favoris');
        $this->addSql('CREATE INDEX IDX_8933C432BDE905D8 ON favoris (id_programme)');
        $this->addSql('ALTER TABLE favoris ADD CONSTRAINT `favoris_ibfk_2` FOREIGN KEY (id_programme) REFERENCES programme (id_programme) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE programme DROP FOREIGN KEY `programme_ibfk_1`');
        $this->addSql('ALTER TABLE programme CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX id_categorie ON programme');
        $this->addSql('CREATE INDEX IDX_3DDCB9FFC9486A13 ON programme (id_categorie)');
        $this->addSql('ALTER TABLE programme ADD CONSTRAINT `programme_ibfk_1` FOREIGN KEY (id_categorie) REFERENCES categorie (id_categorie) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY `reservation_ibfk_1`');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY `reservation_ibfk_2`');
        $this->addSql('ALTER TABLE reservation CHANGE date_reservation date_reservation DATETIME DEFAULT NULL, CHANGE statut statut ENUM(\'confirmée\',\'annulée\',\'en attente\')');
        $this->addSql('DROP INDEX id_seance ON reservation');
        $this->addSql('CREATE INDEX IDX_42C84955F94A48E3 ON reservation (id_seance)');
        $this->addSql('DROP INDEX id_utilisateur ON reservation');
        $this->addSql('CREATE INDEX IDX_42C8495550EAE44 ON reservation (id_utilisateur)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT `reservation_ibfk_1` FOREIGN KEY (id_seance) REFERENCES seance (id_seance) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT `reservation_ibfk_2` FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seance CHANGE titre titre VARCHAR(100) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE lieu lieu VARCHAR(150) DEFAULT NULL, CHANGE date_debut date_debut DATETIME DEFAULT NULL, CHANGE date_fin date_fin DATETIME DEFAULT NULL, CHANGE capacite capacite INT DEFAULT NULL');
        $this->addSql('ALTER TABLE therapeute CHANGE date_inscription date_inscription DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX email ON therapeute');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A5CB96AE7927C74 ON therapeute (email)');
        $this->addSql('ALTER TABLE utilisateur CHANGE date_inscription date_inscription DATETIME DEFAULT NULL, CHANGE role role ENUM(\'admin\',\'client\')');
        $this->addSql('DROP INDEX email ON utilisateur');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE achat CHANGE statut statut ENUM(\'actif\', \'expiré\', \'annulé\') DEFAULT NULL');
        $this->addSql('ALTER TABLE favoris DROP FOREIGN KEY FK_8933C432BDE905D8');
        $this->addSql('CREATE UNIQUE INDEX id_utilisateur ON favoris (id_utilisateur, id_programme)');
        $this->addSql('DROP INDEX idx_8933c432bde905d8 ON favoris');
        $this->addSql('CREATE INDEX id_programme ON favoris (id_programme)');
        $this->addSql('ALTER TABLE favoris ADD CONSTRAINT FK_8933C432BDE905D8 FOREIGN KEY (id_programme) REFERENCES programme (id_programme) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE programme DROP FOREIGN KEY FK_3DDCB9FFC9486A13');
        $this->addSql('ALTER TABLE programme CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_3ddcb9ffc9486a13 ON programme');
        $this->addSql('CREATE INDEX id_categorie ON programme (id_categorie)');
        $this->addSql('ALTER TABLE programme ADD CONSTRAINT FK_3DDCB9FFC9486A13 FOREIGN KEY (id_categorie) REFERENCES categorie (id_categorie) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955F94A48E3');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495550EAE44');
        $this->addSql('ALTER TABLE reservation CHANGE date_reservation date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE statut statut ENUM(\'confirmée\', \'annulée\', \'en attente\') DEFAULT \'en attente\'');
        $this->addSql('DROP INDEX idx_42c84955f94a48e3 ON reservation');
        $this->addSql('CREATE INDEX id_seance ON reservation (id_seance)');
        $this->addSql('DROP INDEX idx_42c8495550eae44 ON reservation');
        $this->addSql('CREATE INDEX id_utilisateur ON reservation (id_utilisateur)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955F94A48E3 FOREIGN KEY (id_seance) REFERENCES seance (id_seance) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495550EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seance CHANGE titre titre VARCHAR(100) NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE lieu lieu VARCHAR(150) NOT NULL, CHANGE date_debut date_debut DATETIME NOT NULL, CHANGE date_fin date_fin DATETIME NOT NULL, CHANGE capacite capacite INT NOT NULL');
        $this->addSql('ALTER TABLE therapeute CHANGE date_inscription date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('DROP INDEX uniq_a5cb96ae7927c74 ON therapeute');
        $this->addSql('CREATE UNIQUE INDEX email ON therapeute (email)');
        $this->addSql('ALTER TABLE utilisateur CHANGE date_inscription date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE role role ENUM(\'admin\', \'client\') NOT NULL');
        $this->addSql('DROP INDEX uniq_1d1c63b3e7927c74 ON utilisateur');
        $this->addSql('CREATE UNIQUE INDEX email ON utilisateur (email)');
    }
}
