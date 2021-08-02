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
use Doctrine\DBAL\Types\Types;

/**
 * @version 1.15
 */
final class Version20210802152259 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the date column to the timesheet table';
    }

    public function up(Schema $schema): void
    {
        $timesheet = $schema->getTable('kimai2_timesheet');
        $timesheet->addColumn('date_tz', Types::DATE_MUTABLE, ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $timesheet = $schema->getTable('kimai2_timesheet');
        $timesheet->dropColumn('date_tz');
    }
}
