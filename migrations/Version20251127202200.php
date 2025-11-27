<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127202200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add authentication tables for SaaS system: refresh_token, email_verification_token, password_reset_token, login_history';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        // Refresh Token table
        $this->addSql('CREATE TABLE refresh_token (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT DEFAULT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            is_revoked TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_C74F21955F37A13B (token),
            INDEX IDX_C74F2195A76ED395 (user_id),
            INDEX IDX_C74F2195E2021A5F (expires_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_C74F2195A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // Email Verification Token table
        $this->addSql('CREATE TABLE email_verification_token (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT DEFAULT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            is_used TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_4562D4E55F37A13B (token),
            INDEX IDX_4562D4E5A76ED395 (user_id),
            INDEX IDX_4562D4E5E2021A5F (expires_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE email_verification_token ADD CONSTRAINT FK_4562D4E5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // Password Reset Token table
        $this->addSql('CREATE TABLE password_reset_token (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT DEFAULT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            is_used TINYINT(1) NOT NULL DEFAULT 0,
            ip_address VARCHAR(45) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_6B7BA4B65F37A13B (token),
            INDEX IDX_6B7BA4B6A76ED395 (user_id),
            INDEX IDX_6B7BA4B6E2021A5F (expires_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE password_reset_token ADD CONSTRAINT FK_6B7BA4B6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // Login History table
        $this->addSql('CREATE TABLE login_history (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT DEFAULT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT NOT NULL,
            success TINYINT(1) NOT NULL,
            failure_reason VARCHAR(255) DEFAULT NULL,
            location VARCHAR(255) DEFAULT NULL,
            device VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_C6689377A76ED395 (user_id),
            INDEX IDX_C668937729C1004E (ip_address),
            INDEX IDX_C6689377E8F57861 (success),
            INDEX IDX_C6689377B23DB7B8 (created_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE login_history ADD CONSTRAINT FK_C6689377A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE refresh_token DROP FOREIGN KEY FK_C74F2195A76ED395');
        $this->addSql('ALTER TABLE email_verification_token DROP FOREIGN KEY FK_4562D4E5A76ED395');
        $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY FK_6B7BA4B6A76ED395');
        $this->addSql('ALTER TABLE login_history DROP FOREIGN KEY FK_C6689377A76ED395');

        $this->addSql('DROP TABLE refresh_token');
        $this->addSql('DROP TABLE email_verification_token');
        $this->addSql('DROP TABLE password_reset_token');
        $this->addSql('DROP TABLE login_history');
    }
}
