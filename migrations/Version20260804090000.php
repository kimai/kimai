<?php

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
 * @version 2.64
 */
final class Version20260804090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add locked_until to project';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_projects');

        if (!$table->hasColumn('locked_until')) {
            $table->addColumn('locked_until', Types::DATE_IMMUTABLE, ['notnull' => false]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_projects');

        if ($table->hasColumn('locked_until')) {
            $table->dropColumn('locked_until');
        }
    }
}
