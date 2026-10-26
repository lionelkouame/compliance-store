<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261024071500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist the whole Holder aggregate and attach documents to their holder.';
    }

    public function up(Schema $schema): void
    {
        // The new NOT NULL columns have no meaningful default for existing rows. Both
        // tables were introduced while the feature was unreleased, so refuse to run
        // on data rather than invent values: clear the rows first, then migrate.
        $this->abortIf(
            0 < (int) $this->connection->fetchOne('SELECT count(*) FROM holder'),
            'The holder table must be empty before this migration (birth_date and status have no default).',
        );
        $this->abortIf(
            0 < (int) $this->connection->fetchOne('SELECT count(*) FROM document'),
            'The document table must be empty before this migration (holder_id has no default).',
        );

        // Holder: align name with HolderName (255), persist birth date, status and GDPR timestamps.
        $this->addSql('ALTER TABLE holder ALTER name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE holder ADD birth_date DATE NOT NULL');
        $this->addSql('ALTER TABLE holder ADD status VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE holder ADD consent_collected_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE holder ADD erasure_requested_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE holder ADD anonymized_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        // Document: reference to its holder by identifier (no FK: aggregates are linked by id).
        $this->addSql('ALTER TABLE document ADD holder_id VARCHAR(36) NOT NULL');
        $this->addSql('CREATE INDEX idx_document_holder_id ON document (holder_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_document_holder_id');
        $this->addSql('ALTER TABLE document DROP holder_id');

        $this->addSql('ALTER TABLE holder DROP anonymized_at');
        $this->addSql('ALTER TABLE holder DROP erasure_requested_at');
        $this->addSql('ALTER TABLE holder DROP consent_collected_at');
        $this->addSql('ALTER TABLE holder DROP status');
        $this->addSql('ALTER TABLE holder DROP birth_date');
        $this->addSql('ALTER TABLE holder ALTER name TYPE VARCHAR(20)');
    }
}
