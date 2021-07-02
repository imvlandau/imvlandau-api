<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210702172443 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX uniq_c8c96b25e7927c74');
        $this->addSql('DROP INDEX uniq_c8c96b255f37a13b');
        $this->addSql('ALTER TABLE attendees ALTER token DROP DEFAULT');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE attendees ALTER token SET DEFAULT 0');
        $this->addSql('CREATE UNIQUE INDEX uniq_c8c96b25e7927c74 ON attendees (email)');
        $this->addSql('CREATE UNIQUE INDEX uniq_c8c96b255f37a13b ON attendees (token)');
    }
}
