<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220531010821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dbs (id INT AUTO_INCREMENT NOT NULL, account_id INT NOT NULL, name VARCHAR(255) NOT NULL, label VARCHAR(255) NOT NULL, server VARCHAR(255) NOT NULL, username VARCHAR(64) NOT NULL, password VARCHAR(64) NOT NULL, port VARCHAR(64) NOT NULL, type VARCHAR(16) NOT NULL, encoding VARCHAR(128) NOT NULL, active SMALLINT NOT NULL, tables LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE dbs');
        $this->addSql('ALTER TABLE query CHANGE date_created date_created DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE date_modified date_modified DATETIME DEFAULT CURRENT_TIMESTAMP');
    }
}
