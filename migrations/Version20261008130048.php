<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261008130048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create identity_document table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE identity_document (type VARCHAR(20) NOT NULL, country VARCHAR(3) NOT NULL, number VARCHAR(20) NOT NULL, expires_at DATE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_identity_document_type_country_number ON identity_document (type, country, number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE identity_document');
    }
}
