<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Every document now belongs to a real holder (clean architecture review, finding C3).
 *
 * Existing documents were never linked to any holder (StoreDocument used a
 * hard-coded id that matched no holder, and the column did not exist): they
 * cannot be attributed, so they are deleted before holder_id becomes mandatory.
 * Their encrypted payloads remain in object storage (MinIO) and must be purged
 * separately if needed.
 */
final class Version20261114110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add mandatory document.holder_id (deletes documents that were never linked to a holder)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM document');
        $this->addSql('ALTER TABLE document ADD holder_id VARCHAR(36) NOT NULL');
        $this->addSql('CREATE INDEX idx_document_holder_id ON document (holder_id)');
    }

    public function down(Schema $schema): void
    {
        // The documents deleted by up() cannot be restored.
        $this->addSql('DROP INDEX idx_document_holder_id');
        $this->addSql('ALTER TABLE document DROP holder_id');
    }
}
