<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the jurisdiction table (dynamic territorial jurisdictions, US-000b).
 */
final class Version20260810070601 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the jurisdiction table (dynamic territorial jurisdictions, US-000b).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE jurisdiction (code VARCHAR(64) NOT NULL, label VARCHAR(255) NOT NULL, region VARCHAR(32) NOT NULL, country VARCHAR(3) DEFAULT NULL, sub_region VARCHAR(64) DEFAULT NULL, applicable_frameworks JSON NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_73C28F3477153098 ON jurisdiction (code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE jurisdiction');
    }
}
