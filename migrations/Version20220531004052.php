<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220531004052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE column_prop (id INT AUTO_INCREMENT NOT NULL, query_id INT DEFAULT NULL, db VARCHAR(255) NOT NULL, db_table VARCHAR(255) NOT NULL, metatype VARCHAR(2) DEFAULT NULL, col_name VARCHAR(255) NOT NULL, hidden SMALLINT DEFAULT NULL, readonly SMALLINT DEFAULT NULL, required SMALLINT DEFAULT NULL, wysiwyg SMALLINT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, align VARCHAR(16) DEFAULT NULL, width VARCHAR(255) DEFAULT NULL, linkto VARCHAR(255) DEFAULT NULL, edittype VARCHAR(255) DEFAULT NULL, conditional_format VARCHAR(255) DEFAULT NULL, customrule VARCHAR(255) DEFAULT NULL, default_value VARCHAR(255) DEFAULT NULL, format VARCHAR(255) DEFAULT NULL, hyperlink VARCHAR(255) DEFAULT NULL, property VARCHAR(255) DEFAULT NULL, img VARCHAR(255) DEFAULT NULL, file_upload VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE column_prop');
    }
}
