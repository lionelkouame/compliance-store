<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261024100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Anonymized holders carry no birth date (GDPR art. 17).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE holder ALTER birth_date DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Rows anonymized in the meantime have no birth date to restore: refuse rather than invent one.
        $this->abortIf(
            0 < (int) $this->connection->fetchOne('SELECT count(*) FROM holder WHERE birth_date IS NULL'),
            'Some holders have no birth date (anonymized): birth_date cannot be made NOT NULL again.',
        );
        $this->addSql('ALTER TABLE holder ALTER birth_date SET NOT NULL');
    }
}
