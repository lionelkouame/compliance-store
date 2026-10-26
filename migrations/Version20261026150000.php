<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261026150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Holder email, dropped on anonymization (GDPR art. 17).';
    }

    public function up(Schema $schema): void
    {
        // IF NOT EXISTS: some local databases already got the column from an
        // earlier draft of Version20261024020529 (NOT NULL, VARCHAR(225)).
        $this->addSql('ALTER TABLE holder ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE holder ALTER email TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE holder ALTER email DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE holder DROP email');
    }
}
