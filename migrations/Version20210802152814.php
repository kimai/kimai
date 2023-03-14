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
 * @version 1.15
 */
final class Version20210802152814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fills the new date column in the timesheet table';
    }

    public function up(Schema $schema): void
    {
        $fetch = $this->connection->prepare('SELECT id, start_time, timezone FROM kimai2_timesheet WHERE date_tz IS NULL');
        $result = $fetch->executeQuery();

        $timezones = [];
        foreach (\DateTimeZone::listIdentifiers() as $tz) {
            $timezones[$tz] = new \DateTimeZone($tz);
        }

        foreach ($result->iterateAssociative() as $row) {
            if (!isset($timezones[$row['timezone']])) {
                $timezones[$row['timezone']] = new \DateTimeZone($row['timezone']);
            }
            $date = new \DateTime($row['start_time'], $timezones['UTC']);
            $date->setTimezone($timezones[$row['timezone']]);
            $this->addSql('UPDATE kimai2_timesheet SET date_tz = ? WHERE id = ?', [$date->format('Y-m-d'), $row['id']]);
        }

        $result->free();

        $this->addSql('ALTER TABLE kimai2_timesheet ALTER date_tz DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $timesheet = $schema->getTable('kimai2_timesheet');
        $timesheet->modifyColumn('date_tz', ['notnull' => false]);

        $this->preventEmptyMigrationWarning();
    }
}
