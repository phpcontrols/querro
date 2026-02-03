<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop slack_webhooks table - Slack integration removed
 */
final class Version20251230235900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop slack_webhooks table as Slack integration has been removed from the application';
    }

    public function up(Schema $schema): void
    {
        // Drop slack_webhooks table
        $this->addSql('DROP TABLE IF EXISTS slack_webhooks');
    }

    public function down(Schema $schema): void
    {
        // Recreate slack_webhooks table for rollback
        $this->addSql('CREATE TABLE slack_webhooks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            account_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            webhook_url VARCHAR(512) NOT NULL,
            channel_name VARCHAR(255) DEFAULT NULL,
            active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }
}
