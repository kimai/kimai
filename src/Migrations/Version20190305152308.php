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
 * - rename mail to email in customer table
 * - introducing foreign keys in SQLite tables
 * - converts all decimal to float values, as decimals are treated as string in PHP:
 *   https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#decimal
 *
 * @version 0.9
 */
final class Version20190305152308 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $customers = 'kimai2_customers';
        $projects = 'kimai2_projects';
        $activities = 'kimai2_activities';
        $timesheet = 'kimai2_timesheet';
        $users = 'kimai2_users';

        if ($this->isPlatformSqlite()) {
            // first backup of ALL tables
            $this->addSql('DROP INDEX IDX_4F60C6B181C06096');
            $this->addSql('DROP INDEX IDX_4F60C6B18D93D649');
            $this->addSql('DROP INDEX IDX_4F60C6B1166D1F9C');
            $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_timesheet AS SELECT id, user, activity_id, project_id, start_time, end_time, timezone, duration, description, rate, fixed_rate, hourly_rate, exported FROM ' . $timesheet);

            $this->addSql('DROP INDEX IDX_8811FE1C166D1F9C');
            $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_activities AS SELECT id, project_id, name, comment, visible, fixed_rate, hourly_rate FROM ' . $activities);

            $this->addSql('DROP INDEX IDX_407F12069395C3F3');
            $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_projects AS SELECT id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate FROM ' . $projects);

            $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_customers AS SELECT id, name, number, comment, visible, company, contact, address, country, currency, phone, fax, mobile, mail, homepage, timezone, fixed_rate, hourly_rate FROM ' . $customers);

            // now we can drop and re-create the tables
            $this->addSql('DROP TABLE ' . $customers);
            $this->addSql('CREATE TABLE ' . $customers . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL COLLATE BINARY, number VARCHAR(50) DEFAULT NULL COLLATE BINARY, comment CLOB DEFAULT NULL COLLATE BINARY, visible BOOLEAN NOT NULL, company VARCHAR(255) DEFAULT NULL COLLATE BINARY, contact VARCHAR(255) DEFAULT NULL COLLATE BINARY, address CLOB DEFAULT NULL COLLATE BINARY, country VARCHAR(2) NOT NULL COLLATE BINARY, currency VARCHAR(3) NOT NULL COLLATE BINARY, phone VARCHAR(255) DEFAULT NULL COLLATE BINARY, fax VARCHAR(255) DEFAULT NULL COLLATE BINARY, mobile VARCHAR(255) DEFAULT NULL COLLATE BINARY, email VARCHAR(255) DEFAULT NULL COLLATE BINARY, homepage VARCHAR(255) DEFAULT NULL COLLATE BINARY, timezone VARCHAR(255) NOT NULL COLLATE BINARY, fixed_rate DOUBLE PRECISION DEFAULT NULL, hourly_rate DOUBLE PRECISION DEFAULT NULL)');
            $this->addSql('INSERT INTO ' . $customers . ' (id, name, number, comment, visible, company, contact, address, country, currency, phone, fax, mobile, email, homepage, timezone, fixed_rate, hourly_rate) SELECT id, name, number, comment, visible, company, contact, address, country, currency, phone, fax, mobile, mail, homepage, timezone, fixed_rate, hourly_rate FROM __temp__kimai2_customers');
            $this->addSql('DROP TABLE __temp__kimai2_customers');

            $this->addSql('DROP TABLE ' . $projects);
            $this->addSql('CREATE TABLE ' . $projects . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, customer_id INTEGER NOT NULL, name VARCHAR(255) NOT NULL COLLATE BINARY, order_number CLOB DEFAULT NULL COLLATE BINARY, comment CLOB DEFAULT NULL COLLATE BINARY, visible BOOLEAN NOT NULL, budget DOUBLE PRECISION NOT NULL, fixed_rate DOUBLE PRECISION DEFAULT NULL, hourly_rate DOUBLE PRECISION DEFAULT NULL, CONSTRAINT FK_407F12069395C3F3 FOREIGN KEY (customer_id) REFERENCES ' . $customers . ' (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
            $this->addSql('INSERT INTO ' . $projects . ' (id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate) SELECT id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate FROM __temp__kimai2_projects');
            $this->addSql('DROP TABLE __temp__kimai2_projects');
            $this->addSql('CREATE INDEX IDX_407F12069395C3F3 ON ' . $projects . ' (customer_id)');

            $this->addSql('DROP TABLE ' . $activities);
            $this->addSql('CREATE TABLE ' . $activities . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, project_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL COLLATE BINARY, comment CLOB DEFAULT NULL COLLATE BINARY, visible BOOLEAN NOT NULL, fixed_rate DOUBLE PRECISION DEFAULT NULL, hourly_rate DOUBLE PRECISION DEFAULT NULL, CONSTRAINT FK_8811FE1C166D1F9C FOREIGN KEY (project_id) REFERENCES ' . $projects . ' (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
            $this->addSql('INSERT INTO ' . $activities . ' (id, project_id, name, comment, visible, fixed_rate, hourly_rate) SELECT id, project_id, name, comment, visible, fixed_rate, hourly_rate FROM __temp__kimai2_activities');
            $this->addSql('DROP TABLE __temp__kimai2_activities');
            $this->addSql('CREATE INDEX IDX_8811FE1C166D1F9C ON ' . $activities . ' (project_id)');

            $this->addSql('DROP TABLE ' . $timesheet);
            $this->addSql('CREATE TABLE ' . $timesheet . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user INTEGER NOT NULL, activity_id INTEGER NOT NULL, project_id INTEGER NOT NULL, start_time DATETIME NOT NULL --(DC2Type:datetime)
        , timezone VARCHAR(64) NOT NULL COLLATE BINARY, duration INTEGER DEFAULT NULL, description CLOB DEFAULT NULL COLLATE BINARY, exported BOOLEAN NOT NULL, end_time DATETIME DEFAULT NULL --(DC2Type:datetime)
        , rate DOUBLE PRECISION NOT NULL, fixed_rate DOUBLE PRECISION DEFAULT NULL, hourly_rate DOUBLE PRECISION DEFAULT NULL, CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES ' . $users . ' (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4F60C6B181C06096 FOREIGN KEY (activity_id) REFERENCES ' . $activities . ' (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4F60C6B1166D1F9C FOREIGN KEY (project_id) REFERENCES ' . $projects . ' (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
            $this->addSql('INSERT INTO ' . $timesheet . ' (id, user, activity_id, project_id, start_time, end_time, timezone, duration, description, rate, fixed_rate, hourly_rate, exported) SELECT id, user, activity_id, project_id, start_time, end_time, timezone, duration, description, rate, fixed_rate, hourly_rate, exported FROM __temp__kimai2_timesheet');
            $this->addSql('DROP TABLE __temp__kimai2_timesheet');
            $this->addSql('CREATE INDEX IDX_4F60C6B181C06096 ON ' . $timesheet . ' (activity_id)');
            $this->addSql('CREATE INDEX IDX_4F60C6B18D93D649 ON ' . $timesheet . ' (user)');
            $this->addSql('CREATE INDEX IDX_4F60C6B1166D1F9C ON ' . $timesheet . ' (project_id)');
        } else {
            $this->addSql('ALTER TABLE ' . $activities . ' CHANGE fixed_rate fixed_rate DOUBLE PRECISION DEFAULT NULL, CHANGE hourly_rate hourly_rate DOUBLE PRECISION DEFAULT NULL');
            $this->addSql('ALTER TABLE ' . $customers . ' CHANGE mail email VARCHAR(255) DEFAULT NULL, CHANGE fixed_rate fixed_rate DOUBLE PRECISION DEFAULT NULL, CHANGE hourly_rate hourly_rate DOUBLE PRECISION DEFAULT NULL');
            $this->addSql('ALTER TABLE ' . $projects . ' CHANGE budget budget DOUBLE PRECISION NOT NULL, CHANGE fixed_rate fixed_rate DOUBLE PRECISION DEFAULT NULL, CHANGE hourly_rate hourly_rate DOUBLE PRECISION DEFAULT NULL');
            $this->addSql('ALTER TABLE ' . $timesheet . ' CHANGE rate rate DOUBLE PRECISION NOT NULL, CHANGE fixed_rate fixed_rate DOUBLE PRECISION DEFAULT NULL, CHANGE hourly_rate hourly_rate DOUBLE PRECISION DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $customers = 'kimai2_customers';
        $projects = 'kimai2_projects';
        $activities = 'kimai2_activities';
        $timesheet = 'kimai2_timesheet';

        if ($this->isPlatformSqlite()) {
            // first backup of ALL tables
            $this->addSql('DROP INDEX IDX_8811FE1C166D1F9C');
            $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_activities AS SELECT id, project_id, name, comment, visible, fixed_rate, hourly_rate FROM ' . $activities);

            $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_customers AS SELECT id, name, number, comment, visible, company, contact, address, country, currency, phone, fax, mobile, email, homepage, timezone, fixed_rate, hourly_rate FROM ' . $customers);

            $this->addSql('DROP INDEX IDX_407F12069395C3F3');
            $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_projects AS SELECT id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate FROM ' . $projects);

            $this->addSql('DROP INDEX IDX_4F60C6B1166D1F9C');
            $this->addSql('DROP INDEX IDX_4F60C6B18D93D649');
            $this->addSql('DROP INDEX IDX_4F60C6B181C06096');
            $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_timesheet AS SELECT id, user, activity_id, project_id, start_time, end_time, timezone, duration, description, rate, fixed_rate, hourly_rate, exported FROM ' . $timesheet);

            // now we can drop and re-create the tables
            $this->addSql('DROP TABLE ' . $activities);
            $this->addSql('CREATE TABLE ' . $activities . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, project_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL, comment CLOB DEFAULT NULL, visible BOOLEAN NOT NULL, fixed_rate NUMERIC(10, 2) DEFAULT NULL, hourly_rate NUMERIC(10, 2) DEFAULT NULL)');
            $this->addSql('INSERT INTO ' . $activities . ' (id, project_id, name, comment, visible, fixed_rate, hourly_rate) SELECT id, project_id, name, comment, visible, fixed_rate, hourly_rate FROM __temp__kimai2_activities');
            $this->addSql('DROP TABLE __temp__kimai2_activities');
            $this->addSql('CREATE INDEX IDX_8811FE1C166D1F9C ON ' . $activities . ' (project_id)');

            $this->addSql('DROP TABLE ' . $customers);
            $this->addSql('CREATE TABLE ' . $customers . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, number VARCHAR(50) DEFAULT NULL, comment CLOB DEFAULT NULL, visible BOOLEAN NOT NULL, company VARCHAR(255) DEFAULT NULL, contact VARCHAR(255) DEFAULT NULL, address CLOB DEFAULT NULL, country VARCHAR(2) NOT NULL, currency VARCHAR(3) NOT NULL, phone VARCHAR(255) DEFAULT NULL, fax VARCHAR(255) DEFAULT NULL, mobile VARCHAR(255) DEFAULT NULL, mail VARCHAR(255) DEFAULT NULL, homepage VARCHAR(255) DEFAULT NULL, timezone VARCHAR(255) NOT NULL, fixed_rate NUMERIC(10, 2) DEFAULT NULL, hourly_rate NUMERIC(10, 2) DEFAULT NULL)');
            $this->addSql('INSERT INTO ' . $customers . ' (id, name, number, comment, visible, company, contact, address, country, currency, phone, fax, mobile, mail, homepage, timezone, fixed_rate, hourly_rate) SELECT id, name, number, comment, visible, company, contact, address, country, currency, phone, fax, mobile, email, homepage, timezone, fixed_rate, hourly_rate FROM __temp__kimai2_customers');
            $this->addSql('DROP TABLE __temp__kimai2_customers');

            $this->addSql('DROP TABLE ' . $projects);
            $this->addSql('CREATE TABLE ' . $projects . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, customer_id INTEGER NOT NULL, name VARCHAR(255) NOT NULL, order_number CLOB DEFAULT NULL, comment CLOB DEFAULT NULL, visible BOOLEAN NOT NULL, budget NUMERIC(10, 2) NOT NULL, fixed_rate NUMERIC(10, 2) DEFAULT NULL, hourly_rate NUMERIC(10, 2) DEFAULT NULL)');
            $this->addSql('INSERT INTO ' . $projects . ' (id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate) SELECT id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate FROM __temp__kimai2_projects');
            $this->addSql('DROP TABLE __temp__kimai2_projects');
            $this->addSql('CREATE INDEX IDX_407F12069395C3F3 ON ' . $projects . ' (customer_id)');

            $this->addSql('DROP TABLE ' . $timesheet);
            $this->addSql('CREATE TABLE ' . $timesheet . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user INTEGER NOT NULL, activity_id INTEGER NOT NULL, project_id INTEGER NOT NULL, start_time DATETIME NOT NULL --(DC2Type:datetime)
        , timezone VARCHAR(64) NOT NULL, duration INTEGER DEFAULT NULL, description CLOB DEFAULT NULL, exported BOOLEAN NOT NULL, end_time DATETIME DEFAULT NULL --(DC2Type:datetime)
        , rate NUMERIC(10, 2) NOT NULL, fixed_rate NUMERIC(10, 2) DEFAULT NULL, hourly_rate NUMERIC(10, 2) DEFAULT NULL)');
            $this->addSql('INSERT INTO ' . $timesheet . ' (id, user, activity_id, project_id, start_time, end_time, timezone, duration, description, rate, fixed_rate, hourly_rate, exported) SELECT id, user, activity_id, project_id, start_time, end_time, timezone, duration, description, rate, fixed_rate, hourly_rate, exported FROM __temp__kimai2_timesheet');
            $this->addSql('DROP TABLE __temp__kimai2_timesheet');
            $this->addSql('CREATE INDEX IDX_4F60C6B1166D1F9C ON ' . $timesheet . ' (project_id)');
            $this->addSql('CREATE INDEX IDX_4F60C6B18D93D649 ON ' . $timesheet . ' (user)');
            $this->addSql('CREATE INDEX IDX_4F60C6B181C06096 ON ' . $timesheet . ' (activity_id)');
        } else {
            $this->addSql('ALTER TABLE ' . $activities . ' CHANGE fixed_rate fixed_rate NUMERIC(10, 2) DEFAULT NULL, CHANGE hourly_rate hourly_rate NUMERIC(10, 2) DEFAULT NULL');
            $this->addSql('ALTER TABLE ' . $customers . ' CHANGE email mail VARCHAR(255) DEFAULT NULL, CHANGE fixed_rate fixed_rate NUMERIC(10, 2) DEFAULT NULL, CHANGE hourly_rate hourly_rate NUMERIC(10, 2) DEFAULT NULL');
            $this->addSql('ALTER TABLE ' . $projects . ' CHANGE budget budget NUMERIC(10, 2) NOT NULL, CHANGE fixed_rate fixed_rate NUMERIC(10, 2) DEFAULT NULL, CHANGE hourly_rate hourly_rate NUMERIC(10, 2) DEFAULT NULL');
            $this->addSql('ALTER TABLE ' . $timesheet . ' CHANGE rate rate NUMERIC(10, 2) NOT NULL, CHANGE fixed_rate fixed_rate NUMERIC(10, 2) DEFAULT NULL, CHANGE hourly_rate hourly_rate NUMERIC(10, 2) DEFAULT NULL');
        }
    }
}
