<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop the document.metadata column (country/retentionYears were hardcoded;
 * this must become a dynamic feature instead).
 */
final class Version20260821140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop the document.metadata column (feature removal, to be replaced by a dynamic mechanism).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document DROP metadata');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE document ADD metadata JSON DEFAULT '{}' NOT NULL");
    }
}
