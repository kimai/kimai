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

class Version20190226205245 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $platform = $this->getPlatform();

        if (!in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }

        $timesheetTags = $this->getTableName('timesheet_tags');
        $tags = $this->getTableName('tags');

        if ($platform === 'sqlite') {
            $this->addSql('CREATE TABLE ' . $timesheetTags . ' (timesheet_id INTEGER NOT NULL, tag_id INTEGER (11) NOT NULL, PRIMARY KEY(timesheet_id, tag_id))');
            $this->addSql('CREATE INDEX IDX_E3284EFEABDD46BE ON ' . $timesheetTags . ' (timesheet_id ASC)');
            $this->addSql('CREATE INDEX IDX_E3284EFEBAD26311 ON ' . $timesheetTags . ' (tag_id ASC)');
            $this->addSql('CREATE TABLE ' . $tags . ' (id INT(11) NOT NULL, tag_name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        } else {
            $this->addSql('CREATE TABLE ' . $timesheetTags . ' (timesheet_id INT(11) NOT NULL, tag_id INT(11) NOT NULL, PRIMARY KEY (timesheet_id, tag_id)) DEFAULT CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('CREATE INDEX IDX_E3284EFEABDD46BE ON ' . $timesheetTags . ' (timesheet_id ASC)');
            $this->addSql('CREATE INDEX IDX_E3284EFEBAD26311 ON ' . $timesheetTags . ' (tag_id ASC)');
            $this->addSql('CREATE TABLE ' . $tags . ' (id INT(11) NOT NULL AUTO_INCREMENT, tag_name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ENGINE = InnoDB');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->getPlatform();

        if (!in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }

        $timesheetTags = $this->getTableName('timesheet_tags');
        $tags = $this->getTableName('tags');

        if ($platform === 'sqlite') {
            $this->addSql('DROP TABLE ' . $timesheetTags);
            $this->addSql('DROP INDEX IDX_E3284EFEABDD46BE');
            $this->addSql('DROP INDEX IDX_E3284EFEBAD26311');
            $this->addSql('DROP TABLE ' . $tags);
        } else {
            $this->addSql('DROP TABLE ' . $timesheetTags);
            $this->addSql('DROP INDEX IDX_E3284EFEABDD46BE');
            $this->addSql('DROP INDEX IDX_E3284EFEBAD26311');
            $this->addSql('DROP TABLE ' . $tags);
        }
    }
}
