<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour créer la table magic_links
 *
 * Table pour l'authentification sans mot de passe (Magic Link).
 * Les tokens sont stockés hashés (SHA-256) et à usage unique.
 */
final class Version20251210150300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table magic_links pour l\'authentification sans mot de passe';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE magic_links (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            is_used TINYINT(1) NOT NULL DEFAULT 0,
            used_at DATETIME DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            used_from_ip VARCHAR(45) DEFAULT NULL,
            UNIQUE INDEX UNIQ_MAGIC_LINK_TOKEN (token_hash),
            INDEX idx_magic_link_token (token_hash),
            INDEX idx_magic_link_user (user_id, is_used),
            INDEX idx_magic_link_expires (expires_at),
            CONSTRAINT FK_MAGIC_LINK_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE magic_links');
    }
}
