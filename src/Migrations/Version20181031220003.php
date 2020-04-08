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
 * Migration that adds the project column to timesheets table, to allow global activities.
 * It also fixes foreign-key columns on timesheet and projects table, which are not allowed.
 */
final class Version20181031220003 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $timesheet = 'kimai2_timesheet';
        $projects = 'kimai2_projects';
        $activities = 'kimai2_activities';
        $users = 'kimai2_users';
        $customers = 'kimai2_customers';

        if ($this->isPlatformSqlite()) {
            // project table
            $this->addSql('DROP INDEX IDX_407F12069395C3F3');
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $projects . ' AS SELECT id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate FROM ' . $projects);
            $this->addSql('DROP TABLE ' . $projects);
            $this->addSql('CREATE TABLE ' . $projects . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, customer_id INTEGER NOT NULL, name VARCHAR(255) NOT NULL COLLATE BINARY, order_number CLOB DEFAULT NULL COLLATE BINARY, comment CLOB DEFAULT NULL COLLATE BINARY, visible BOOLEAN NOT NULL, budget NUMERIC(10, 2) NOT NULL, fixed_rate NUMERIC(10, 2) DEFAULT NULL, hourly_rate NUMERIC(10, 2) DEFAULT NULL, CONSTRAINT FK_407F12069395C3F3 FOREIGN KEY (customer_id) REFERENCES ' . $customers . ' (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
            $this->addSql('INSERT INTO ' . $projects . ' (id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate) SELECT id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate FROM __temp__' . $projects);
            $this->addSql('DROP TABLE __temp__' . $projects);
            $this->addSql('CREATE INDEX IDX_407F12069395C3F3 ON ' . $projects . ' (customer_id)');
            // timesheet table
            $this->addSql('DROP INDEX IDX_4F60C6B18D93D649');
            $this->addSql('DROP INDEX IDX_4F60C6B181C06096');
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $timesheet . ' AS SELECT id, user, activity_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate FROM ' . $timesheet);
            $this->addSql('DROP TABLE ' . $timesheet);
            $this->addSql('CREATE TABLE ' . $timesheet . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user INTEGER DEFAULT NULL, activity_id INTEGER DEFAULT NULL, project_id INTEGER DEFAULT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, duration INTEGER DEFAULT NULL, description CLOB DEFAULT NULL COLLATE BINARY, rate NUMERIC(10, 2) NOT NULL, fixed_rate NUMERIC(10, 2) DEFAULT NULL, hourly_rate NUMERIC(10, 2) DEFAULT NULL, CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES ' . $users . ' (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4F60C6B181C06096 FOREIGN KEY (activity_id) REFERENCES ' . $activities . ' (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4F60C6B1166D1F9C FOREIGN KEY (project_id) REFERENCES ' . $projects . ' (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
            $this->addSql('INSERT INTO ' . $timesheet . ' (id, user, activity_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate) SELECT id, user, activity_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate FROM __temp__' . $timesheet);
            $this->addSql('DROP TABLE __temp__' . $timesheet);
            $this->addSql('CREATE INDEX IDX_4F60C6B18D93D649 ON ' . $timesheet . ' (user)');
            $this->addSql('CREATE INDEX IDX_4F60C6B181C06096 ON ' . $timesheet . ' (activity_id)');
            $this->addSql('CREATE INDEX IDX_4F60C6B1166D1F9C ON ' . $timesheet . ' (project_id)');
        } else {
            // project table
            $this->addSql('ALTER TABLE ' . $projects . ' DROP FOREIGN KEY FK_407F12069395C3F3');
            $this->addSql('ALTER TABLE ' . $projects . ' CHANGE customer_id customer_id INT NOT NULL');
            $this->addSql('ALTER TABLE ' . $projects . ' ADD CONSTRAINT FK_407F12069395C3F3 FOREIGN KEY (customer_id) REFERENCES ' . $customers . ' (id) ON DELETE CASCADE');
            // timesheet table
            $this->addSql('ALTER TABLE ' . $timesheet . ' ADD project_id INT DEFAULT NULL AFTER activity_id');
            $this->addSql('CREATE INDEX IDX_4F60C6B1166D1F9C ON ' . $timesheet . ' (project_id)');
        }

        // update timesheet table and insert project_id from activity table
        $this->addSql('UPDATE ' . $timesheet . ' SET project_id = (SELECT project_id FROM ' . $activities . ' WHERE id = activity_id)');

        // now update the timesheet table and disallow null values for all required columns (that was a bug before)
        if ($this->isPlatformSqlite()) {
            $this->addSql('DROP INDEX IDX_4F60C6B181C06096');
            $this->addSql('DROP INDEX IDX_4F60C6B18D93D649');
            $this->addSql('DROP INDEX IDX_4F60C6B1166D1F9C');
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $timesheet . ' AS SELECT id, user, activity_id, project_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate FROM ' . $timesheet);
            $this->addSql('DROP TABLE ' . $timesheet);
            $this->addSql('CREATE TABLE ' . $timesheet . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user INTEGER NOT NULL, activity_id INTEGER NOT NULL, project_id INTEGER NOT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, duration INTEGER DEFAULT NULL, description CLOB DEFAULT NULL COLLATE BINARY, rate NUMERIC(10, 2) NOT NULL, fixed_rate NUMERIC(10, 2) DEFAULT NULL, hourly_rate NUMERIC(10, 2) DEFAULT NULL, CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES ' . $users . ' (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4F60C6B181C06096 FOREIGN KEY (activity_id) REFERENCES ' . $activities . ' (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4F60C6B1166D1F9C FOREIGN KEY (project_id) REFERENCES ' . $projects . ' (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
            $this->addSql('INSERT INTO ' . $timesheet . ' (id, user, activity_id, project_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate) SELECT id, user, activity_id, project_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate FROM __temp__' . $timesheet);
            $this->addSql('DROP TABLE __temp__' . $timesheet);
            $this->addSql('CREATE INDEX IDX_4F60C6B181C06096 ON ' . $timesheet . ' (activity_id)');
            $this->addSql('CREATE INDEX IDX_4F60C6B18D93D649 ON ' . $timesheet . ' (user)');
            $this->addSql('CREATE INDEX IDX_4F60C6B1166D1F9C ON ' . $timesheet . ' (project_id)');
        } else {
            $this->addSql('ALTER TABLE ' . $timesheet . ' DROP FOREIGN KEY FK_4F60C6B18D93D649');
            $this->addSql('ALTER TABLE ' . $timesheet . ' DROP FOREIGN KEY FK_4F60C6B181C06096');
            $this->addSql('ALTER TABLE ' . $timesheet . ' CHANGE project_id project_id INT NOT NULL, CHANGE user user INT NOT NULL, CHANGE activity_id activity_id INT NOT NULL');
            $this->addSql('ALTER TABLE ' . $timesheet . ' ADD CONSTRAINT FK_4F60C6B1166D1F9C FOREIGN KEY (project_id) REFERENCES ' . $projects . ' (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE ' . $timesheet . ' ADD CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES ' . $users . ' (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE ' . $timesheet . ' ADD CONSTRAINT FK_4F60C6B181C06096 FOREIGN KEY (activity_id) REFERENCES ' . $activities . ' (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        $timesheet = 'kimai2_timesheet';
        $projects = 'kimai2_projects';
        $customers = 'kimai2_customers';

        if ($this->isPlatformSqlite()) {
            // project table
            $this->addSql('DROP INDEX IDX_407F12069395C3F3');
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $projects . ' AS SELECT id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate FROM ' . $projects);
            $this->addSql('DROP TABLE ' . $projects);
            $this->addSql('CREATE TABLE ' . $projects . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, order_number CLOB DEFAULT NULL, comment CLOB DEFAULT NULL, visible BOOLEAN NOT NULL, budget NUMERIC(10, 2) NOT NULL, fixed_rate NUMERIC(10, 2) DEFAULT NULL, hourly_rate NUMERIC(10, 2) DEFAULT NULL, customer_id INTEGER DEFAULT NULL)');
            $this->addSql('INSERT INTO ' . $projects . ' (id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate) SELECT id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate FROM __temp__' . $projects);
            $this->addSql('DROP TABLE __temp__' . $projects);
            $this->addSql('CREATE INDEX IDX_407F12069395C3F3 ON ' . $projects . ' (customer_id)');
            // timesheet table
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
            // project table
            $this->addSql('ALTER TABLE ' . $projects . ' DROP FOREIGN KEY FK_407F12069395C3F3');
            $this->addSql('ALTER TABLE ' . $projects . ' CHANGE customer_id customer_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE ' . $projects . ' ADD CONSTRAINT FK_407F12069395C3F3 FOREIGN KEY (customer_id) REFERENCES ' . $customers . ' (id) ON DELETE CASCADE');
            // timesheet table
            $this->addSql('ALTER TABLE ' . $timesheet . ' DROP FOREIGN KEY FK_4F60C6B1166D1F9C');
            $this->addSql('DROP INDEX IDX_4F60C6B1166D1F9C ON ' . $timesheet);
            $this->addSql('ALTER TABLE ' . $timesheet . ' DROP project_id, CHANGE user user INT DEFAULT NULL, CHANGE activity_id activity_id INT DEFAULT NULL');
        }
    }
}
