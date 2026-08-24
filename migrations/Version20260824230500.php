<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824230500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop owner_id column from document table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document DROP COLUMN IF EXISTS owner_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document ADD owner_id VARCHAR(255)');
    }
}
