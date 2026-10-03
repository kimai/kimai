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

/**
 * @version 2.67.0
 */
final class Version20260913124142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the visible column to the Teams table.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_teams');
        if (!$table->hasColumn('visible')) {
            $table->addColumn('visible', 'boolean', ['notnull' => false, 'default' => true]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_teams');
        if ($table->hasColumn('visible')) {
            $table->dropColumn('visible');
        }
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
