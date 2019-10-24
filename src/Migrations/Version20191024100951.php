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
 * Adds the order_date column to the projects table.
 *
 * @version 1.5
 */
final class Version20191024100951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the order_date column to the projects table';
    }

    protected function isSupportingForeignKeys(): bool
    {
        return false;
    }

    public function isTransactional(): bool
    {
        if ($this->isPlatformSqlite()) {
            // does fail if we use transactions, as tables are re-created and foreign keys would fail
            return false;
        }

        return true;
    }

    public function up(Schema $schema): void
    {
        $projects = $schema->getTable('kimai2_projects');
        $projects->addColumn('order_date', 'datetime', ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $projects = $schema->getTable('kimai2_projects');
        $projects->dropColumn('order_date');
    }
}
