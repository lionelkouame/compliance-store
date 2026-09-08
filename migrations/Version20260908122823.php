<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260908122823 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create storage_space table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE storage_space (code VARCHAR(64) NOT NULL, name VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_44A6081A77153098 ON storage_space (code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE storage_space');
    }
}
