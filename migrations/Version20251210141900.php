<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210141900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE email_otps (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, code VARCHAR(6) NOT NULL, email VARCHAR(180) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, is_used TINYINT(1) NOT NULL, used_at DATETIME DEFAULT NULL, attempts INT DEFAULT 0 NOT NULL, purpose VARCHAR(20) NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, INDEX IDX_68FC8130A76ED395 (user_id), INDEX idx_email_otp_lookup (email, created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE email_otps ADD CONSTRAINT FK_68FC8130A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE email_otps DROP FOREIGN KEY FK_68FC8130A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE email_otps
        SQL);
    }
}
