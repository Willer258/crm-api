<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour créer la table account_lockouts
 *
 * Cette table gère le verrouillage progressif des comptes
 * après des tentatives de connexion échouées.
 */
final class Version20251210150100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table account_lockouts pour la protection anti-brute force';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE account_lockouts (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            failed_attempts INT NOT NULL DEFAULT 0,
            locked_until DATETIME DEFAULT NULL,
            last_failed_attempt DATETIME NOT NULL,
            last_ip_address VARCHAR(45) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE INDEX UNIQ_ACCOUNT_LOCKOUT_USER (user_id),
            INDEX idx_user_lockout (user_id, locked_until),
            INDEX idx_ip_lockout (last_ip_address, last_failed_attempt),
            CONSTRAINT FK_ACCOUNT_LOCKOUT_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE account_lockouts');
    }
}
