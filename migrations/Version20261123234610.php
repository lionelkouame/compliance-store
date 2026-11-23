<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Holder emails become unique in the database (issue #84).
 *
 * CreateHolderUseCase already rejects a known email, but two concurrent
 * requests could both pass that check and create duplicate holders. The index
 * makes the second insert fail; HolderRepository reports it as a 409.
 *
 * Fails if duplicate emails already exist: they must be merged first.
 */
final class Version20261123234610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a unique index on holder.email';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D6A868BDE7927C74 ON holder (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_D6A868BDE7927C74');
    }
}
