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
 * Adds the exported column to the timesheet table
 *
 * @version 0.8
 */
final class Version20190124004014 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('kimai2_timesheet')->addColumn('exported', 'boolean', ['notnull' => true, 'default' => false]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('kimai2_timesheet')->dropColumn('exported');
    }
}
