<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210130211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD workspace_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD CONSTRAINT FK_CFBDFA1482D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CFBDFA1482D40A1F ON note (workspace_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE pipeline ADD workspace_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE pipeline ADD CONSTRAINT FK_7DFCD9D982D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_7DFCD9D982D40A1F ON pipeline (workspace_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA1482D40A1F
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_CFBDFA1482D40A1F ON note
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP workspace_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE pipeline DROP FOREIGN KEY FK_7DFCD9D982D40A1F
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_7DFCD9D982D40A1F ON pipeline
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE pipeline DROP workspace_id
        SQL);
    }
}
