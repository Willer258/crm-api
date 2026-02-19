<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260219000719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add CRM Messaging: message_thread and message tables for WhatsApp/Email unified inbox';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, workspace_id INT NOT NULL, thread_id INT NOT NULL, contact_id INT DEFAULT NULL, channel VARCHAR(20) NOT NULL, direction VARCHAR(10) NOT NULL, from_address VARCHAR(255) NOT NULL, to_address VARCHAR(255) NOT NULL, subject VARCHAR(500) DEFAULT NULL, content LONGTEXT NOT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, metadata JSON DEFAULT NULL, read_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B6BD307F82D40A1F (workspace_id), INDEX IDX_B6BD307FE2904019 (thread_id), INDEX IDX_B6BD307FE7A1254A (contact_id), INDEX idx_message_channel (channel), INDEX idx_message_direction (direction), INDEX idx_message_status (status), INDEX idx_message_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE message_thread (id INT AUTO_INCREMENT NOT NULL, workspace_id INT NOT NULL, contact_id INT DEFAULT NULL, channel VARCHAR(20) NOT NULL, subject VARCHAR(255) DEFAULT NULL, contact_address VARCHAR(255) NOT NULL, unread_count INT DEFAULT 0 NOT NULL, status VARCHAR(20) DEFAULT \'open\' NOT NULL, last_message_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_message_preview LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_607D18C82D40A1F (workspace_id), INDEX IDX_607D18CE7A1254A (contact_id), INDEX idx_thread_channel (channel), INDEX idx_thread_last_message (last_message_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FE2904019 FOREIGN KEY (thread_id) REFERENCES message_thread (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE message_thread ADD CONSTRAINT FK_607D18C82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_thread ADD CONSTRAINT FK_607D18CE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F82D40A1F');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FE2904019');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FE7A1254A');
        $this->addSql('ALTER TABLE message_thread DROP FOREIGN KEY FK_607D18C82D40A1F');
        $this->addSql('ALTER TABLE message_thread DROP FOREIGN KEY FK_607D18CE7A1254A');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE message_thread');
    }
}
