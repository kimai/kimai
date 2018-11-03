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
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181031220003 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $platform = $this->getPlatform();

        if (!in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }

        $timesheet = $this->getTableName('timesheet');
        $projects = $this->getTableName('projects');
        $activities = $this->getTableName('activities');

        if ($platform === 'sqlite') {
            $this->addSql('DROP INDEX IDX_4F60C6B18D93D649');
            $this->addSql('DROP INDEX IDX_4F60C6B181C06096');
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $timesheet . ' AS SELECT id, user, activity_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate FROM ' . $timesheet);
            $this->addSql('DROP TABLE ' . $timesheet);
            $this->addSql('CREATE TABLE ' . $timesheet . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user INTEGER DEFAULT NULL, activity_id INTEGER DEFAULT NULL, project_id INTEGER DEFAULT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, duration INTEGER DEFAULT NULL, description CLOB DEFAULT NULL COLLATE BINARY, rate NUMERIC(10, 2) NOT NULL, fixed_rate NUMERIC(10, 2) DEFAULT NULL, hourly_rate NUMERIC(10, 2) DEFAULT NULL, CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES kimai2_users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4F60C6B181C06096 FOREIGN KEY (activity_id) REFERENCES kimai2_activities (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4F60C6B1166D1F9C FOREIGN KEY (project_id) REFERENCES kimai2_projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
            $this->addSql('INSERT INTO ' . $timesheet . ' (id, user, activity_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate) SELECT id, user, activity_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate FROM __temp__' . $timesheet);
            $this->addSql('DROP TABLE __temp__' . $timesheet);
            $this->addSql('CREATE INDEX IDX_4F60C6B18D93D649 ON ' . $timesheet . ' (user)');
            $this->addSql('CREATE INDEX IDX_4F60C6B181C06096 ON ' . $timesheet . ' (activity_id)');
            $this->addSql('CREATE INDEX IDX_4F60C6B1166D1F9C ON ' . $timesheet . ' (project_id)');
        } else {
            $this->addSql('ALTER TABLE ' . $timesheet . ' ADD project_id INT DEFAULT NULL AFTER activity_id, CHANGE user user INT DEFAULT NULL, CHANGE activity_id activity_id INT DEFAULT NULL, CHANGE end_time end_time DATETIME DEFAULT NULL, CHANGE duration duration INT DEFAULT NULL, CHANGE fixed_rate fixed_rate NUMERIC(10, 2) DEFAULT NULL, CHANGE hourly_rate hourly_rate NUMERIC(10, 2) DEFAULT NULL');
            $this->addSql('ALTER TABLE ' . $timesheet . ' ADD CONSTRAINT FK_4F60C6B1166D1F9C FOREIGN KEY (project_id) REFERENCES ' . $projects . ' (id) ON DELETE CASCADE');
            $this->addSql('CREATE INDEX IDX_4F60C6B1166D1F9C ON ' . $timesheet . ' (project_id)');
        }

        $this->addSql('UPDATE ' . $timesheet . ' SET project_id = (SELECT project_id FROM ' . $activities . ' WHERE id = activity_id)');
    }

    public function down(Schema $schema): void
    {
        $platform = $this->getPlatform();

        if (!in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }

        $timesheet = $this->getTableName('timesheet');

        if ($platform === 'sqlite') {
            $this->addSql('DROP INDEX IDX_4F60C6B1166D1F9C');
            $this->addSql('DROP INDEX IDX_4F60C6B18D93D649');
            $this->addSql('DROP INDEX IDX_4F60C6B181C06096');
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $timesheet . ' AS SELECT id, user, activity_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate FROM ' . $timesheet);
            $this->addSql('DROP TABLE ' . $timesheet);
            $this->addSql('CREATE TABLE ' . $timesheet . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user INTEGER DEFAULT NULL, activity_id INTEGER DEFAULT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, duration INTEGER DEFAULT NULL, description CLOB DEFAULT NULL, rate NUMERIC(10, 2) NOT NULL, fixed_rate NUMERIC(10, 2) DEFAULT NULL, hourly_rate NUMERIC(10, 2) DEFAULT NULL)');
            $this->addSql('INSERT INTO ' . $timesheet . ' (id, user, activity_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate) SELECT id, user, activity_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate FROM __temp__' . $timesheet);
            $this->addSql('DROP TABLE __temp__' . $timesheet);
            $this->addSql('CREATE INDEX IDX_4F60C6B18D93D649 ON ' . $timesheet . ' (user)');
            $this->addSql('CREATE INDEX IDX_4F60C6B181C06096 ON ' . $timesheet . ' (activity_id)');
        } else {
            $this->addSql('ALTER TABLE ' . $timesheet . ' DROP FOREIGN KEY FK_4F60C6B1166D1F9C');
            $this->addSql('DROP INDEX IDX_4F60C6B1166D1F9C ON ' . $timesheet);
            $this->addSql('ALTER TABLE ' . $timesheet . ' DROP project_id, CHANGE user user INT DEFAULT NULL, CHANGE activity_id activity_id INT DEFAULT NULL, CHANGE end_time end_time DATETIME DEFAULT \'NULL\', CHANGE duration duration INT DEFAULT NULL, CHANGE fixed_rate fixed_rate NUMERIC(10, 2) DEFAULT \'NULL\', CHANGE hourly_rate hourly_rate NUMERIC(10, 2) DEFAULT \'NULL\'');
        }
    }
}
