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
 * New feature: tagging of timesheet records
 *
 * @version 1.0
 */
class Version20190510205245 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $timesheetTags = $this->getTableName('timesheet_tags');
        $tags = $this->getTableName('tags');

        if ($this->isPlatformSqlite()) {
            $this->addSql('CREATE TABLE ' . $timesheetTags . ' (timesheet_id INTEGER NOT NULL, tag_id INTEGER (11) NOT NULL, PRIMARY KEY(timesheet_id, tag_id))');
            $this->addSql('CREATE INDEX IDX_E3284EFEABDD46BE ON ' . $timesheetTags . ' (timesheet_id ASC)');
            $this->addSql('CREATE INDEX IDX_E3284EFEBAD26311 ON ' . $timesheetTags . ' (tag_id ASC)');
            $this->addSql('CREATE TABLE ' . $tags . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL)');
        } else {
            $this->addSql('CREATE TABLE ' . $timesheetTags . ' (timesheet_id INT(11) NOT NULL, tag_id INT(11) NOT NULL, PRIMARY KEY (timesheet_id, tag_id))');
            $this->addSql('CREATE INDEX IDX_E3284EFEABDD46BE ON ' . $timesheetTags . ' (timesheet_id ASC)');
            $this->addSql('CREATE INDEX IDX_E3284EFEBAD26311 ON ' . $timesheetTags . ' (tag_id ASC)');
            $this->addSql('CREATE TABLE ' . $tags . ' (id INT(11) NOT NULL AUTO_INCREMENT, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        }
    }

    public function down(Schema $schema): void
    {
        $timesheetTags = $this->getTableName('timesheet_tags');
        $tags = $this->getTableName('tags');

        if ($this->isPlatformSqlite()) {
            $this->addSql('DROP INDEX IDX_E3284EFEABDD46BE');
            $this->addSql('DROP INDEX IDX_E3284EFEBAD26311');
        } else {
            $this->addSql('DROP INDEX IDX_E3284EFEABDD46BE ON ' . $timesheetTags);
            $this->addSql('DROP INDEX IDX_E3284EFEBAD26311 ON ' . $timesheetTags);
        }
        $this->addSql('DROP TABLE ' . $timesheetTags);
        $this->addSql('DROP TABLE ' . $tags);
    }
}
