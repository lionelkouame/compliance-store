<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260803213327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the document table (encrypted document storage, ADR 0002).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE document (document_type VARCHAR(64) NOT NULL, owner_id VARCHAR(255) NOT NULL, metadata JSON NOT NULL, file_hash VARCHAR(64) NOT NULL, wrapped_data_key BYTEA NOT NULL, storage_key VARCHAR NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE document');
    }
}
