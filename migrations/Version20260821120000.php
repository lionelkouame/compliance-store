<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop the legal_framework and jurisdiction tables (feature removal).
 */
final class Version20260821120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop the legal_framework and jurisdiction tables (feature removal).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE legal_framework');
        $this->addSql('DROP TABLE jurisdiction');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE jurisdiction (code VARCHAR(64) NOT NULL, label VARCHAR(255) NOT NULL, region VARCHAR(32) NOT NULL, country VARCHAR(3) DEFAULT NULL, sub_region VARCHAR(64) DEFAULT NULL, applicable_frameworks JSON NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_73C28F3477153098 ON jurisdiction (code)');
        $this->addSql('CREATE TABLE legal_framework (code VARCHAR(64) NOT NULL, name VARCHAR(255) NOT NULL, official_reference VARCHAR(255) NOT NULL, regulatory_authority VARCHAR(255) NOT NULL, jurisdiction_code VARCHAR(64) NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_71BF514877153098 ON legal_framework (code)');
    }
}
