<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181012180003 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TABLE kimai2_system_preference (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL)');
        $this->addSql('DROP INDEX IDX_8811FE1C166D1F9C');
        $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_activities AS SELECT id, project_id, name, comment, visible, fixed_rate, hourly_rate FROM kimai2_activities');
        $this->addSql('DROP TABLE kimai2_activities');
        $this->addSql('CREATE TABLE kimai2_activities (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, project_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL COLLATE BINARY, comment CLOB DEFAULT NULL COLLATE BINARY, visible BOOLEAN NOT NULL, fixed_rate NUMERIC(10, 2) DEFAULT NULL, hourly_rate NUMERIC(10, 2) DEFAULT NULL, CONSTRAINT FK_8811FE1C166D1F9C FOREIGN KEY (project_id) REFERENCES kimai2_projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO kimai2_activities (id, project_id, name, comment, visible, fixed_rate, hourly_rate) SELECT id, project_id, name, comment, visible, fixed_rate, hourly_rate FROM __temp__kimai2_activities');
        $this->addSql('DROP TABLE __temp__kimai2_activities');
        $this->addSql('CREATE INDEX IDX_8811FE1C166D1F9C ON kimai2_activities (project_id)');
        $this->addSql('DROP INDEX IDX_407F12069395C3F3');
        $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_projects AS SELECT id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate FROM kimai2_projects');
        $this->addSql('DROP TABLE kimai2_projects');
        $this->addSql('CREATE TABLE kimai2_projects (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, customer_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL COLLATE BINARY, order_number CLOB DEFAULT NULL COLLATE BINARY, comment CLOB DEFAULT NULL COLLATE BINARY, visible BOOLEAN NOT NULL, budget NUMERIC(10, 2) NOT NULL, fixed_rate NUMERIC(10, 2) DEFAULT NULL, hourly_rate NUMERIC(10, 2) DEFAULT NULL, CONSTRAINT FK_407F12069395C3F3 FOREIGN KEY (customer_id) REFERENCES kimai2_customers (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO kimai2_projects (id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate) SELECT id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate FROM __temp__kimai2_projects');
        $this->addSql('DROP TABLE __temp__kimai2_projects');
        $this->addSql('CREATE INDEX IDX_407F12069395C3F3 ON kimai2_projects (customer_id)');
        $this->addSql('DROP INDEX IDX_4F60C6B181C06096');
        $this->addSql('DROP INDEX IDX_4F60C6B18D93D649');
        $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_timesheet AS SELECT id, user, activity_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate FROM kimai2_timesheet');
        $this->addSql('DROP TABLE kimai2_timesheet');
        $this->addSql('CREATE TABLE kimai2_timesheet (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user INTEGER DEFAULT NULL, activity_id INTEGER DEFAULT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, duration INTEGER DEFAULT NULL, description CLOB DEFAULT NULL COLLATE BINARY, rate NUMERIC(10, 2) NOT NULL, fixed_rate NUMERIC(10, 2) DEFAULT NULL, hourly_rate NUMERIC(10, 2) DEFAULT NULL, CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES kimai2_users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4F60C6B181C06096 FOREIGN KEY (activity_id) REFERENCES kimai2_activities (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO kimai2_timesheet (id, user, activity_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate) SELECT id, user, activity_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate FROM __temp__kimai2_timesheet');
        $this->addSql('DROP TABLE __temp__kimai2_timesheet');
        $this->addSql('CREATE INDEX IDX_4F60C6B181C06096 ON kimai2_timesheet (activity_id)');
        $this->addSql('CREATE INDEX IDX_4F60C6B18D93D649 ON kimai2_timesheet (user)');
        $this->addSql('DROP INDEX UNIQ_8D08F631A76ED3955E237E06');
        $this->addSql('DROP INDEX IDX_8D08F631A76ED395');
        $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_user_preferences AS SELECT id, user_id, name, value FROM kimai2_user_preferences');
        $this->addSql('DROP TABLE kimai2_user_preferences');
        $this->addSql('CREATE TABLE kimai2_user_preferences (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER DEFAULT NULL, name VARCHAR(50) NOT NULL COLLATE BINARY, value VARCHAR(255) DEFAULT NULL COLLATE BINARY, CONSTRAINT FK_8D08F631A76ED395 FOREIGN KEY (user_id) REFERENCES kimai2_users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO kimai2_user_preferences (id, user_id, name, value) SELECT id, user_id, name, value FROM __temp__kimai2_user_preferences');
        $this->addSql('DROP TABLE __temp__kimai2_user_preferences');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D08F631A76ED3955E237E06 ON kimai2_user_preferences (user_id, name)');
        $this->addSql('CREATE INDEX IDX_8D08F631A76ED395 ON kimai2_user_preferences (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP TABLE kimai2_system_preference');
        $this->addSql('DROP INDEX IDX_8811FE1C166D1F9C');
        $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_activities AS SELECT id, project_id, name, comment, visible, fixed_rate, hourly_rate FROM kimai2_activities');
        $this->addSql('DROP TABLE kimai2_activities');
        $this->addSql('CREATE TABLE kimai2_activities (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, project_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL, comment CLOB DEFAULT NULL, visible BOOLEAN NOT NULL, fixed_rate NUMERIC(10, 2) DEFAULT NULL, hourly_rate NUMERIC(10, 2) DEFAULT NULL)');
        $this->addSql('INSERT INTO kimai2_activities (id, project_id, name, comment, visible, fixed_rate, hourly_rate) SELECT id, project_id, name, comment, visible, fixed_rate, hourly_rate FROM __temp__kimai2_activities');
        $this->addSql('DROP TABLE __temp__kimai2_activities');
        $this->addSql('CREATE INDEX IDX_8811FE1C166D1F9C ON kimai2_activities (project_id)');
        $this->addSql('DROP INDEX IDX_407F12069395C3F3');
        $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_projects AS SELECT id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate FROM kimai2_projects');
        $this->addSql('DROP TABLE kimai2_projects');
        $this->addSql('CREATE TABLE kimai2_projects (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, customer_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL, order_number CLOB DEFAULT NULL, comment CLOB DEFAULT NULL, visible BOOLEAN NOT NULL, budget NUMERIC(10, 2) NOT NULL, fixed_rate NUMERIC(10, 2) DEFAULT NULL, hourly_rate NUMERIC(10, 2) DEFAULT NULL)');
        $this->addSql('INSERT INTO kimai2_projects (id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate) SELECT id, customer_id, name, order_number, comment, visible, budget, fixed_rate, hourly_rate FROM __temp__kimai2_projects');
        $this->addSql('DROP TABLE __temp__kimai2_projects');
        $this->addSql('CREATE INDEX IDX_407F12069395C3F3 ON kimai2_projects (customer_id)');
        $this->addSql('DROP INDEX IDX_4F60C6B18D93D649');
        $this->addSql('DROP INDEX IDX_4F60C6B181C06096');
        $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_timesheet AS SELECT id, user, activity_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate FROM kimai2_timesheet');
        $this->addSql('DROP TABLE kimai2_timesheet');
        $this->addSql('CREATE TABLE kimai2_timesheet (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user INTEGER DEFAULT NULL, activity_id INTEGER DEFAULT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, duration INTEGER DEFAULT NULL, description CLOB DEFAULT NULL, rate NUMERIC(10, 2) NOT NULL, fixed_rate NUMERIC(10, 2) DEFAULT NULL, hourly_rate NUMERIC(10, 2) DEFAULT NULL)');
        $this->addSql('INSERT INTO kimai2_timesheet (id, user, activity_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate) SELECT id, user, activity_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate FROM __temp__kimai2_timesheet');
        $this->addSql('DROP TABLE __temp__kimai2_timesheet');
        $this->addSql('CREATE INDEX IDX_4F60C6B18D93D649 ON kimai2_timesheet (user)');
        $this->addSql('CREATE INDEX IDX_4F60C6B181C06096 ON kimai2_timesheet (activity_id)');
        $this->addSql('DROP INDEX IDX_8D08F631A76ED395');
        $this->addSql('DROP INDEX UNIQ_8D08F631A76ED3955E237E06');
        $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_user_preferences AS SELECT id, user_id, name, value FROM kimai2_user_preferences');
        $this->addSql('DROP TABLE kimai2_user_preferences');
        $this->addSql('CREATE TABLE kimai2_user_preferences (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER DEFAULT NULL, name VARCHAR(50) NOT NULL, value VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO kimai2_user_preferences (id, user_id, name, value) SELECT id, user_id, name, value FROM __temp__kimai2_user_preferences');
        $this->addSql('DROP TABLE __temp__kimai2_user_preferences');
        $this->addSql('CREATE INDEX IDX_8D08F631A76ED395 ON kimai2_user_preferences (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D08F631A76ED3955E237E06 ON kimai2_user_preferences (user_id, name)');
    }
}
