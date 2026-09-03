<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop the document.storage_key column.
 *
 * The storage key is a pure storage-backend concern (ADR 0009) derived
 * deterministically from the document id ("documents/{id}"), so it no longer
 * needs to be persisted: it is recomputed by the storage adapter on demand.
 */
final class Version20260903131500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop document.storage_key: key is derived from the document id (ADR 0009).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document DROP COLUMN storage_key');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE document ADD COLUMN storage_key VARCHAR NOT NULL DEFAULT ''");
        $this->addSql("UPDATE document SET storage_key = 'documents/' || id");
        $this->addSql('ALTER TABLE document ALTER COLUMN storage_key DROP DEFAULT');
    }
}
