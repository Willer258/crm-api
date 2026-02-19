<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour créer la table oauth_connections
 *
 * Table pour stocker les connexions OAuth (Google, GitHub, Keycloak).
 * Permet aux utilisateurs de se connecter via des fournisseurs externes.
 */
final class Version20251210150400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table oauth_connections pour l\'authentification OAuth2/OIDC';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE oauth_connections (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            provider VARCHAR(50) NOT NULL,
            provider_user_id VARCHAR(255) NOT NULL,
            provider_email VARCHAR(180) DEFAULT NULL,
            access_token LONGTEXT DEFAULT NULL,
            refresh_token LONGTEXT DEFAULT NULL,
            token_expires_at DATETIME DEFAULT NULL,
            connected_at DATETIME NOT NULL,
            last_used_at DATETIME DEFAULT NULL,
            provider_data JSON DEFAULT NULL,
            INDEX idx_oauth_user (user_id),
            INDEX idx_oauth_provider_user (provider, provider_user_id),
            UNIQUE INDEX unique_oauth_connection (provider, provider_user_id),
            CONSTRAINT FK_OAUTH_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE oauth_connections');
    }
}
