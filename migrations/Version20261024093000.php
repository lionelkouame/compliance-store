<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261024093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'API clients authenticated with a hashed bearer API key.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_client (id VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, key_hash VARCHAR(64) NOT NULL, roles JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_api_client_name ON api_client (name)');
        $this->addSql('CREATE UNIQUE INDEX uniq_api_client_key_hash ON api_client (key_hash)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE api_client');
    }
}
