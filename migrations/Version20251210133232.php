<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210133232 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add file management fields to Asset entity (workspace_id, real_name, uuid, UserObjectTrait fields) and company information fields to Workspace';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE asset ADD workspace_id INT NOT NULL, ADD real_name VARCHAR(255) NOT NULL, ADD uuid CHAR(36) DEFAULT NULL COMMENT '(DC2Type:uuid)', ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD create_by VARCHAR(255) DEFAULT NULL, ADD update_by VARCHAR(255) DEFAULT NULL, ADD remove_at DATETIME DEFAULT NULL, ADD remove_by VARCHAR(255) DEFAULT NULL, ADD created_from_ip VARCHAR(45) DEFAULT NULL, ADD updated_from_ip VARCHAR(45) DEFAULT NULL, ADD restored_at DATETIME DEFAULT NULL, ADD restored_by VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset ADD CONSTRAINT FK_2AF5A5C82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_asset_workspace ON asset (workspace_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workspace ADD description LONGTEXT DEFAULT NULL, ADD industry VARCHAR(100) DEFAULT NULL, ADD employee_count INT DEFAULT NULL, ADD website VARCHAR(255) DEFAULT NULL, ADD phone VARCHAR(50) DEFAULT NULL, ADD email VARCHAR(255) DEFAULT NULL, ADD address LONGTEXT DEFAULT NULL, ADD city VARCHAR(100) DEFAULT NULL, ADD postal_code VARCHAR(10) DEFAULT NULL, ADD country VARCHAR(100) DEFAULT NULL, ADD siret VARCHAR(20) DEFAULT NULL, ADD vat_number VARCHAR(20) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE asset DROP FOREIGN KEY FK_2AF5A5C82D40A1F
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_asset_workspace ON asset
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset DROP workspace_id, DROP real_name, DROP uuid, DROP created_at, DROP updated_at, DROP create_by, DROP update_by, DROP remove_at, DROP remove_by, DROP created_from_ip, DROP updated_from_ip, DROP restored_at, DROP restored_by
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workspace DROP description, DROP industry, DROP employee_count, DROP website, DROP phone, DROP email, DROP address, DROP city, DROP postal_code, DROP country, DROP siret, DROP vat_number
        SQL);
    }
}
