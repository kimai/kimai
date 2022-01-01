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
        // project table
        $this->addSql('ALTER TABLE kimai2_projects DROP FOREIGN KEY FK_407F12069395C3F3');
        $this->addSql('ALTER TABLE kimai2_projects CHANGE customer_id customer_id INT NOT NULL');
        $this->addSql('ALTER TABLE kimai2_projects ADD CONSTRAINT FK_407F12069395C3F3 FOREIGN KEY (customer_id) REFERENCES kimai2_customers (id) ON DELETE CASCADE');
        // timesheet table
        $this->addSql('ALTER TABLE kimai2_timesheet ADD project_id INT DEFAULT NULL AFTER activity_id');
        $this->addSql('CREATE INDEX IDX_4F60C6B1166D1F9C ON kimai2_timesheet (project_id)');

        // update timesheet table and insert project_id from activity table
        $this->addSql('UPDATE kimai2_timesheet SET project_id = (SELECT project_id FROM kimai2_activities WHERE id = activity_id)');

        // now update the timesheet table and disallow null values for all required columns (that was a bug before)
        $this->addSql('ALTER TABLE kimai2_timesheet DROP FOREIGN KEY FK_4F60C6B18D93D649');
        $this->addSql('ALTER TABLE kimai2_timesheet DROP FOREIGN KEY FK_4F60C6B181C06096');
        $this->addSql('ALTER TABLE kimai2_timesheet CHANGE project_id project_id INT NOT NULL, CHANGE user user INT NOT NULL, CHANGE activity_id activity_id INT NOT NULL');
        $this->addSql('ALTER TABLE kimai2_timesheet ADD CONSTRAINT FK_4F60C6B1166D1F9C FOREIGN KEY (project_id) REFERENCES kimai2_projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE kimai2_timesheet ADD CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES kimai2_users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE kimai2_timesheet ADD CONSTRAINT FK_4F60C6B181C06096 FOREIGN KEY (activity_id) REFERENCES kimai2_activities (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // project table
        $this->addSql('ALTER TABLE kimai2_projects DROP FOREIGN KEY FK_407F12069395C3F3');
        $this->addSql('ALTER TABLE kimai2_projects CHANGE customer_id customer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_projects ADD CONSTRAINT FK_407F12069395C3F3 FOREIGN KEY (customer_id) REFERENCES kimai2_customers (id) ON DELETE CASCADE');
        // timesheet table
        $this->addSql('ALTER TABLE kimai2_timesheet DROP FOREIGN KEY FK_4F60C6B1166D1F9C');
        $this->addSql('DROP INDEX IDX_4F60C6B1166D1F9C ON kimai2_timesheet');
        $this->addSql('ALTER TABLE kimai2_timesheet DROP project_id, CHANGE user user INT DEFAULT NULL, CHANGE activity_id activity_id INT DEFAULT NULL');
    }
}
