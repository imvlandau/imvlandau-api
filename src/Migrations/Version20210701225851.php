<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210701225851 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE attendees ADD token INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C8C96B25E7927C74 ON attendees (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C8C96B255F37A13B ON attendees (token)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX UNIQ_C8C96B25E7927C74');
        $this->addSql('DROP INDEX UNIQ_C8C96B255F37A13B');
        $this->addSql('ALTER TABLE attendees DROP token');
    }
}
