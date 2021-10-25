<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210924223834 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE attendees_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE participant_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE participant (id INT NOT NULL, name VARCHAR(50) NOT NULL, email VARCHAR(50) NOT NULL, token INT NOT NULL, mobile VARCHAR(50) NOT NULL, companion_1 VARCHAR(50) DEFAULT NULL, companion_2 VARCHAR(50) DEFAULT NULL, companion_3 VARCHAR(50) DEFAULT NULL, companion_4 VARCHAR(50) DEFAULT NULL, has_been_scanned BOOLEAN DEFAULT \'false\' NOT NULL, has_been_scanned_amount INT DEFAULT 0 NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D79F6B11E7927C74 ON participant (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D79F6B115F37A13B ON participant (token)');
        $this->addSql('DROP TABLE attendees');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE participant_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE attendees_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE attendees (id INT NOT NULL, name VARCHAR(50) NOT NULL, email VARCHAR(50) NOT NULL, mobile VARCHAR(50) NOT NULL, companion_1 VARCHAR(50) DEFAULT NULL, companion_2 VARCHAR(50) DEFAULT NULL, companion_3 VARCHAR(50) DEFAULT NULL, companion_4 VARCHAR(50) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, token INT NOT NULL, has_been_scanned BOOLEAN DEFAULT \'false\' NOT NULL, has_been_scanned_amount INT DEFAULT 0 NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_c8c96b255f37a13b ON attendees (token)');
        $this->addSql('CREATE UNIQUE INDEX uniq_c8c96b25e7927c74 ON attendees (email)');
        $this->addSql('DROP TABLE participant');
    }
}
