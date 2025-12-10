<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210125412 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE workspace (id INT AUTO_INCREMENT NOT NULL, uuid CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)', name VARCHAR(255) NOT NULL, slug VARCHAR(100) NOT NULL, logo VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D940019D17F50A6 (uuid), UNIQUE INDEX UNIQ_8D940019989D9B62 (slug), INDEX idx_slug (slug), INDEX idx_is_active (is_active), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE workspace_member (id INT AUTO_INCREMENT NOT NULL, workspace_id INT NOT NULL, user_id INT NOT NULL, role VARCHAR(20) NOT NULL, is_active TINYINT(1) NOT NULL, joined_at DATETIME NOT NULL, INDEX idx_workspace (workspace_id), INDEX idx_user (user_id), INDEX idx_role (role), UNIQUE INDEX unique_workspace_user (workspace_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workspace_member ADD CONSTRAINT FK_40242BD082D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workspace_member ADD CONSTRAINT FK_40242BD0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE activity ADD workspace_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE activity ADD CONSTRAINT FK_AC74095A82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AC74095A82D40A1F ON activity (workspace_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE company ADD workspace_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE company ADD CONSTRAINT FK_4FBF094F82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4FBF094F82D40A1F ON company (workspace_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact ADD workspace_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact ADD CONSTRAINT FK_4C62E63882D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4C62E63882D40A1F ON contact (workspace_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE deal ADD workspace_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE deal ADD CONSTRAINT FK_E3FEC11682D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E3FEC11682D40A1F ON deal (workspace_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD current_workspace_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD CONSTRAINT FK_8D93D6497D65B4C4 FOREIGN KEY (current_workspace_id) REFERENCES workspace (id) ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D93D6497D65B4C4 ON user (current_workspace_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE activity DROP FOREIGN KEY FK_AC74095A82D40A1F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE company DROP FOREIGN KEY FK_4FBF094F82D40A1F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact DROP FOREIGN KEY FK_4C62E63882D40A1F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE deal DROP FOREIGN KEY FK_E3FEC11682D40A1F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP FOREIGN KEY FK_8D93D6497D65B4C4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workspace_member DROP FOREIGN KEY FK_40242BD082D40A1F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workspace_member DROP FOREIGN KEY FK_40242BD0A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE workspace
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE workspace_member
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_AC74095A82D40A1F ON activity
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE activity DROP workspace_id
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_4FBF094F82D40A1F ON company
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE company DROP workspace_id
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_4C62E63882D40A1F ON contact
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact DROP workspace_id
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_E3FEC11682D40A1F ON deal
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE deal DROP workspace_id
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8D93D6497D65B4C4 ON user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP current_workspace_id
        SQL);
    }
}
