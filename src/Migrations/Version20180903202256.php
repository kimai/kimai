<?php declare(strict_types=1);

namespace DoctrineMigrations;

use App\Doctrine\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adding hourly_rate and fixed_rate to timesheet table
 */
final class Version20180903202256 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $platform = $this->getPlatform();

        if (!in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }

        $timesheet = $this->getTableName('timesheet');
        $user = $this->getTableName('users');
        $activity = $this->getTableName('activities');

        if ($platform === 'sqlite') {
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $timesheet . ' AS SELECT id, user, activity_id, start_time, end_time, duration, description, rate FROM ' . $timesheet);
            $this->addSql('DROP TABLE ' . $timesheet);
            $this->addSql('CREATE TABLE ' . $timesheet . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user INTEGER DEFAULT NULL, activity_id INTEGER DEFAULT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, duration INTEGER DEFAULT NULL, description CLOB DEFAULT NULL COLLATE BINARY, rate NUMERIC(10, 2) NOT NULL, fixed_rate NUMERIC(10, 2) DEFAULT NULL, hourly_rate NUMERIC(10, 2) DEFAULT NULL, CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES ' . $user . ' (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4F60C6B181C06096 FOREIGN KEY (activity_id) REFERENCES ' . $activity . ' (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
            $this->addSql('INSERT INTO ' . $timesheet . ' (id, user, activity_id, start_time, end_time, duration, description, rate, fixed_rate, hourly_rate) SELECT id, user, activity_id, start_time, end_time, duration, description, rate, null, null FROM __temp__' . $timesheet);
            $this->addSql('DROP TABLE __temp__' . $timesheet);
            $this->addSql('CREATE INDEX IDX_4F60C6B181C06096 ON ' . $timesheet . ' (activity_id)');
            $this->addSql('CREATE INDEX IDX_4F60C6B18D93D649 ON ' . $timesheet . ' (user)');
        } else {
            $this->addSql('ALTER TABLE ' . $timesheet . ' DROP FOREIGN KEY FK_4F60C6B18D93D649');
            $this->addSql('ALTER TABLE ' . $timesheet . ' ADD fixed_rate NUMERIC(10, 2) DEFAULT NULL, ADD hourly_rate NUMERIC(10, 2) DEFAULT NULL');
            $this->addSql('ALTER TABLE ' . $timesheet . ' ADD CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES ' . $user . ' (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema) : void
    {
        $platform = $this->getPlatform();

        if (!in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }

        $timesheet = $this->getTableName('timesheet');
        $user = $this->getTableName('users');

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
            $this->addSql('ALTER TABLE ' . $timesheet . ' DROP FOREIGN KEY FK_4F60C6B18D93D649');
            $this->addSql('ALTER TABLE ' . $timesheet . ' DROP fixed_rate, DROP hourly_rate');
            $this->addSql('ALTER TABLE ' . $timesheet . ' ADD CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES ' . $user . ' (id)');
        }
    }
}
