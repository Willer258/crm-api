<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour sécuriser les refresh tokens
 *
 * Changements:
 * 1. Renomme la colonne 'token' en 'token_hash' (stockage du hash SHA-256)
 * 2. Réduit la longueur de 128 à 64 caractères (hash SHA-256)
 * 3. Ajoute les colonnes device_name, location, last_used_at pour la gestion des sessions
 * 4. Met à jour les index
 *
 * IMPORTANT: Cette migration invalide tous les refresh tokens existants!
 * Les utilisateurs devront se reconnecter.
 */
final class Version20251210150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sécurisation des refresh tokens: stockage hashé, ajout tracking device/location';
    }

    public function up(Schema $schema): void
    {
        // IMPORTANT: Supprimer tous les tokens existants car ils ne sont pas hashés
        // et causeraient des violations de contrainte unique
        // Les utilisateurs devront se reconnecter
        $this->addSql('DELETE FROM refresh_tokens');

        // Supprime l'ancien index sur token (MySQL syntax)
        $this->addSql('ALTER TABLE refresh_tokens DROP INDEX IF EXISTS idx_refresh_token');
        $this->addSql('ALTER TABLE refresh_tokens DROP INDEX IF EXISTS UNIQ_9BACE7E15F37A13B');

        // Renomme la colonne token en token_hash et réduit sa taille
        $this->addSql('ALTER TABLE refresh_tokens CHANGE token token_hash VARCHAR(64) NOT NULL');

        // Ajoute les nouvelles colonnes pour le tracking des sessions
        $this->addSql('ALTER TABLE refresh_tokens ADD device_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE refresh_tokens ADD location VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE refresh_tokens ADD last_used_at DATETIME DEFAULT NULL');

        // Crée le nouvel index sur token_hash
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9BACE7E1B36989E8 ON refresh_tokens (token_hash)');
        $this->addSql('CREATE INDEX idx_refresh_token_hash ON refresh_tokens (token_hash)');
    }

    public function down(Schema $schema): void
    {
        // Supprime les nouveaux index
        $this->addSql('DROP INDEX IF EXISTS idx_refresh_token_hash ON refresh_tokens');
        $this->addSql('DROP INDEX IF EXISTS UNIQ_9BACE7E1B36989E8 ON refresh_tokens');

        // Supprime les nouvelles colonnes
        $this->addSql('ALTER TABLE refresh_tokens DROP device_name');
        $this->addSql('ALTER TABLE refresh_tokens DROP location');
        $this->addSql('ALTER TABLE refresh_tokens DROP last_used_at');

        // Restaure la colonne originale
        $this->addSql('ALTER TABLE refresh_tokens CHANGE token_hash token VARCHAR(128) NOT NULL');

        // Restaure les index originaux
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9BACE7E15F37A13B ON refresh_tokens (token)');
        $this->addSql('CREATE INDEX idx_refresh_token ON refresh_tokens (token)');
    }
}
