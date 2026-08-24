<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824224000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop document_type column from document table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document DROP COLUMN IF EXISTS document_type');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document ADD document_type VARCHAR(64)');
    }
}
