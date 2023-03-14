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
final class Version20210802160837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the index on the new timesheet statistic date column';
    }

    public function up(Schema $schema): void
    {
        $timesheet = $schema->getTable('kimai2_timesheet');
        $timesheet->modifyColumn('date_tz', ['notnull' => true]);
        $timesheet->addIndex(['date_tz', 'user'], 'IDX_4F60C6B1BDF467148D93D649');
    }

    public function down(Schema $schema): void
    {
        $timesheet = $schema->getTable('kimai2_timesheet');
        $timesheet->modifyColumn('date_tz', ['notnull' => false]);
        $timesheet->dropIndex('IDX_4F60C6B1BDF467148D93D649');
    }
}
