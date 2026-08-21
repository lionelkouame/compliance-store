<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop the regulatory_scope table and the document.regulatory_scope_id column (feature removal).
 */
final class Version20260821130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop the regulatory_scope table and the document.regulatory_scope_id column (feature removal).';
    }

    public function up(Schema $schema): void
    {
        // IF EXISTS: some environments already dropped the FK and renamed the index
        // (see the leftover-FK cleanup applied ahead of this branch's own migration history).
        $this->addSql('ALTER TABLE document DROP CONSTRAINT IF EXISTS fk_d8698a76d8cea02f');
        $this->addSql('DROP INDEX IF EXISTS idx_d8698a76d8cea02f');
        $this->addSql('DROP INDEX IF EXISTS idx_document_regulatory_scope');
        $this->addSql('ALTER TABLE document DROP regulatory_scope_id');
        $this->addSql('DROP TABLE regulatory_scope');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE regulatory_scope (id VARCHAR(36) NOT NULL, label VARCHAR(255) NOT NULL, description TEXT NOT NULL, allowed_document_types JSON NOT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, code VARCHAR(64) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E7A5D42677153098 ON regulatory_scope (code)');
        $this->addSql('ALTER TABLE document ADD regulatory_scope_id VARCHAR(36) NOT NULL');
        $this->addSql('CREATE INDEX idx_document_regulatory_scope ON document (regulatory_scope_id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76D8CEA02F FOREIGN KEY (regulatory_scope_id) REFERENCES regulatory_scope (id)');
    }
}
