<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261024110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Append-only audit trail of actions on holders and documents.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audit_event (id VARCHAR(36) NOT NULL, action VARCHAR(40) NOT NULL, subject_id VARCHAR(36) NOT NULL, actor VARCHAR(100) NOT NULL, occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_audit_event_subject ON audit_event (subject_id, occurred_at)');
        $this->addSql('CREATE INDEX idx_audit_event_occurred_at ON audit_event (occurred_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE audit_event');
    }
}
