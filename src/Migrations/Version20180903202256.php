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
 * Adding hourly_rate and fixed_rate to timesheet table
 */
final class Version20180903202256 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_timesheet ADD COLUMN fixed_rate NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_timesheet ADD COLUMN hourly_rate NUMERIC(10, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $timesheet = $schema->getTable('kimai2_timesheet');

        $timesheet->dropColumn('hourly_rate');
        $timesheet->dropColumn('fixed_rate');
    }
}
