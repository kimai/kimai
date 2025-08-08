<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use App\Doctrine\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * @version 2.x
 */
final class Version20250807232208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ICS calendar sources table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE kimai2_ics_calendar_sources (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, url LONGTEXT NOT NULL, color VARCHAR(7) DEFAULT NULL, enabled TINYINT(1) DEFAULT 1 NOT NULL, last_sync DATETIME DEFAULT NULL, INDEX IDX_20495A72A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE kimai2_ics_calendar_sources ADD CONSTRAINT FK_20495A72A76ED395 FOREIGN KEY (user_id) REFERENCES kimai2_users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_ics_calendar_sources DROP FOREIGN KEY FK_20495A72A76ED395');
        $this->addSql('DROP TABLE kimai2_ics_calendar_sources');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
