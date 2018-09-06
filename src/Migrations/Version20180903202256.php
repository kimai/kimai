<?php

declare(strict_types=1);

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
 * Adding hourly_rate and fixed_rate to timesheet table
 */
final class Version20180903202256 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $platform = $this->getPlatform();

        if (!in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }

        $timesheet = $this->getTableName('timesheet');

        $this->addSql('ALTER TABLE ' . $timesheet . ' ADD COLUMN fixed_rate NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $timesheet . ' ADD COLUMN hourly_rate NUMERIC(10, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $platform = $this->getPlatform();

        if (!in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }

        $timesheet = $this->getTableName('timesheet');

        if ($platform === 'sqlite') {
            $this->addSql('DROP INDEX IDX_4F60C6B18D93D649');
            $this->addSql('DROP INDEX IDX_4F60C6B181C06096');
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $timesheet . ' AS SELECT id, user, activity_id, start_time, end_time, duration, description, rate FROM ' . $timesheet);
            $this->addSql('DROP TABLE ' . $timesheet);
            $this->addSql('CREATE TABLE ' . $timesheet . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user INTEGER DEFAULT NULL, activity_id INTEGER DEFAULT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, duration INTEGER DEFAULT NULL, description CLOB DEFAULT NULL, rate NUMERIC(10, 2) NOT NULL)');
            $this->addSql('INSERT INTO ' . $timesheet . ' (id, user, activity_id, start_time, end_time, duration, description, rate) SELECT id, user, activity_id, start_time, end_time, duration, description, rate FROM __temp__' . $timesheet);
            $this->addSql('DROP TABLE __temp__' . $timesheet);
            $this->addSql('CREATE INDEX IDX_4F60C6B18D93D649 ON ' . $timesheet . ' (user)');
            $this->addSql('CREATE INDEX IDX_4F60C6B181C06096 ON ' . $timesheet . ' (activity_id)');
        } else {
            $this->addSql('ALTER TABLE ' . $timesheet . ' DROP hourly_rate');
            $this->addSql('ALTER TABLE ' . $timesheet . ' DROP fixed_rate');
        }
    }
}
