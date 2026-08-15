<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the legal_framework table (dynamic legal/regulatory frameworks, US-000c).
 */
final class Version20260811112950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the legal_framework table (dynamic legal/regulatory frameworks, US-000c).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE legal_framework (code VARCHAR(64) NOT NULL, name VARCHAR(255) NOT NULL, official_reference VARCHAR(255) NOT NULL, regulatory_authority VARCHAR(255) NOT NULL, jurisdiction_code VARCHAR(64) NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_71BF514877153098 ON legal_framework (code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE legal_framework');
    }
}
