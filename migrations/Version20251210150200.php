<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour créer la table two_factor_auth
 */
final class Version20251210150200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table two_factor_auth pour l\'authentification TOTP';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE two_factor_auth (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            totp_secret VARCHAR(255) DEFAULT NULL,
            is_enabled TINYINT(1) NOT NULL DEFAULT 0,
            recovery_codes JSON NOT NULL,
            enabled_at DATETIME DEFAULT NULL,
            last_used_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE INDEX UNIQ_2FA_USER (user_id),
            CONSTRAINT FK_2FA_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE two_factor_auth');
    }
}
