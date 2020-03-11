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
 * Adds the timezone column to the timesheet table
 * See https://github.com/kevinpapst/kimai2/pull/372 for further information.
 *
 * @version 0.8
 */
final class Version20190201150324 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $timezone = date_default_timezone_get();

        if ($this->isPlatformSqlite()) {
            $this->addSql('ALTER TABLE kimai2_timesheet ADD COLUMN timezone VARCHAR(64) DEFAULT NULL');
        } else {
            $this->addSql('ALTER TABLE kimai2_timesheet ADD timezone VARCHAR(64) NOT NULL');
        }

        $this->addSql('UPDATE kimai2_timesheet SET timezone = "' . $timezone . '"');
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('kimai2_timesheet')->dropColumn('timezone');
    }
}
