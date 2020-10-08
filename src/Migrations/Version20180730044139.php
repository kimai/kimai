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
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migrations fot the "delete user" feature.
 *
 * Adds constraints to the timesheet table, so all timesheet entries will be deleted when a user is deleted.
 */
final class Version20180730044139 extends AbstractMigration
{
    /**
     * @var Index[]
     */
    protected $indexesOld = [];

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     */
    public function up(Schema $schema): void
    {
        $timesheet = 'kimai2_timesheet';
        $user = 'kimai2_users';
        $activity = 'kimai2_activities';

        if ($this->isPlatformSqlite()) {
            $this->addSql('DROP INDEX IDX_4F60C6B181C06096');
            $this->addSql('DROP INDEX IDX_4F60C6B18D93D649');
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $timesheet . ' AS SELECT id, user, activity_id, start_time, end_time, duration, description, rate FROM ' . $timesheet);
            $this->addSql('DROP TABLE ' . $timesheet);
            $this->addSql('CREATE TABLE ' . $timesheet . ' (id INTEGER NOT NULL, user INTEGER DEFAULT NULL, activity_id INTEGER DEFAULT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, duration INTEGER DEFAULT NULL, description CLOB DEFAULT NULL COLLATE BINARY, rate NUMERIC(10, 2) NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES ' . $user . ' (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4F60C6B181C06096 FOREIGN KEY (activity_id) REFERENCES ' . $activity . ' (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
            $this->addSql('INSERT INTO ' . $timesheet . ' (id, user, activity_id, start_time, end_time, duration, description, rate) SELECT id, user, activity_id, start_time, end_time, duration, description, rate FROM __temp__' . $timesheet);
            $this->addSql('DROP TABLE __temp__' . $timesheet);
            $this->addSql('CREATE INDEX IDX_4F60C6B181C06096 ON ' . $timesheet . ' (activity_id)');
            $this->addSql('CREATE INDEX IDX_4F60C6B18D93D649 ON ' . $timesheet . ' (user)');
        } else {
            $this->addSql('ALTER TABLE ' . $timesheet . ' DROP FOREIGN KEY FK_4F60C6B18D93D649');
            $this->addSql('ALTER TABLE ' . $timesheet . ' ADD CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES ' . $user . ' (id) ON DELETE CASCADE');
        }
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     */
    public function down(Schema $schema): void
    {
        $timesheet = 'kimai2_timesheet';
        $user = 'kimai2_users';

        if ($this->isPlatformSqlite()) {
            $this->addSql('DROP INDEX IDX_4F60C6B18D93D649');
            $this->addSql('DROP INDEX IDX_4F60C6B181C06096');
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $timesheet . ' AS SELECT id, user, activity_id, start_time, end_time, duration, description, rate FROM ' . $timesheet);
            $this->addSql('DROP TABLE ' . $timesheet);
            $this->addSql('CREATE TABLE ' . $timesheet . ' (id INTEGER NOT NULL, user INTEGER DEFAULT NULL, activity_id INTEGER DEFAULT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, duration INTEGER DEFAULT NULL, description CLOB DEFAULT NULL, rate NUMERIC(10, 2) NOT NULL, PRIMARY KEY(id))');
            $this->addSql('INSERT INTO ' . $timesheet . ' (id, user, activity_id, start_time, end_time, duration, description, rate) SELECT id, user, activity_id, start_time, end_time, duration, description, rate FROM __temp__' . $timesheet);
            $this->addSql('DROP TABLE __temp__' . $timesheet);
            $this->addSql('CREATE INDEX IDX_4F60C6B18D93D649 ON ' . $timesheet . ' (user)');
            $this->addSql('CREATE INDEX IDX_4F60C6B181C06096 ON ' . $timesheet . ' (activity_id)');
        } else {
            $this->addSql('ALTER TABLE ' . $timesheet . ' DROP FOREIGN KEY FK_4F60C6B18D93D649');
            $this->addSql('ALTER TABLE ' . $timesheet . ' ADD CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES ' . $user . ' (id)');
        }
    }
}
