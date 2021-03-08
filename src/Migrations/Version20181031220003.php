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

        // project table
        $this->addSql('ALTER TABLE ' . $projects . ' DROP FOREIGN KEY FK_407F12069395C3F3');
        $this->addSql('ALTER TABLE ' . $projects . ' CHANGE customer_id customer_id INT NOT NULL');
        $this->addSql('ALTER TABLE ' . $projects . ' ADD CONSTRAINT FK_407F12069395C3F3 FOREIGN KEY (customer_id) REFERENCES ' . $customers . ' (id) ON DELETE CASCADE');
        // timesheet table
        $this->addSql('ALTER TABLE ' . $timesheet . ' ADD project_id INT DEFAULT NULL AFTER activity_id');
        $this->addSql('CREATE INDEX IDX_4F60C6B1166D1F9C ON ' . $timesheet . ' (project_id)');

        // update timesheet table and insert project_id from activity table
        $this->addSql('UPDATE ' . $timesheet . ' SET project_id = (SELECT project_id FROM ' . $activities . ' WHERE id = activity_id)');

        // now update the timesheet table and disallow null values for all required columns (that was a bug before)
        $this->addSql('ALTER TABLE ' . $timesheet . ' DROP FOREIGN KEY FK_4F60C6B18D93D649');
        $this->addSql('ALTER TABLE ' . $timesheet . ' DROP FOREIGN KEY FK_4F60C6B181C06096');
        $this->addSql('ALTER TABLE ' . $timesheet . ' CHANGE project_id project_id INT NOT NULL, CHANGE user user INT NOT NULL, CHANGE activity_id activity_id INT NOT NULL');
        $this->addSql('ALTER TABLE ' . $timesheet . ' ADD CONSTRAINT FK_4F60C6B1166D1F9C FOREIGN KEY (project_id) REFERENCES ' . $projects . ' (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ' . $timesheet . ' ADD CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES ' . $users . ' (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ' . $timesheet . ' ADD CONSTRAINT FK_4F60C6B181C06096 FOREIGN KEY (activity_id) REFERENCES ' . $activities . ' (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $timesheet = 'kimai2_timesheet';
        $projects = 'kimai2_projects';
        $customers = 'kimai2_customers';

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
