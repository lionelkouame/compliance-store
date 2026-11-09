<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261030014055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE holder ADD birth_date DATE NOT NULL');
        $this->addSql('ALTER TABLE holder ADD status VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE holder ADD consent_collected_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE holder ADD erasure_requested_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE holder ADD anonymized_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE holder ALTER name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE holder ALTER email TYPE VARCHAR(255)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE holder DROP birth_date');
        $this->addSql('ALTER TABLE holder DROP status');
        $this->addSql('ALTER TABLE holder DROP consent_collected_at');
        $this->addSql('ALTER TABLE holder DROP erasure_requested_at');
        $this->addSql('ALTER TABLE holder DROP anonymized_at');
        $this->addSql('ALTER TABLE holder ALTER name TYPE VARCHAR(225)');
        $this->addSql('ALTER TABLE holder ALTER email TYPE VARCHAR(225)');
    }
}
