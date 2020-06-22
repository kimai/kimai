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
 * Changing column sizes to prevent index length errors.
 *
 * @version 1.2
 */
final class Version20190813162649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Changing column sizes to prevent index length errors';
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
        $activity = $schema->getTable('kimai2_activities');
        $name = $activity->getColumn('name');
        if ($name->getLength() !== 150) {
            $name->setLength(150);
        }

        $project = $schema->getTable('kimai2_projects');
        $name = $project->getColumn('name');
        if ($name->getLength() !== 150) {
            $name->setLength(150);
        }

        $customer = $schema->getTable('kimai2_customers');
        $name = $customer->getColumn('name');
        if ($name->getLength() !== 150) {
            $name->setLength(150);
        }
        $customer->getColumn('timezone')->setLength(64);
    }

    public function down(Schema $schema): void
    {
        $activity = $schema->getTable('kimai2_activities');
        $activity->getColumn('name')->setLength(255);

        $project = $schema->getTable('kimai2_projects');
        $project->getColumn('name')->setLength(255);

        $customer = $schema->getTable('kimai2_customers');
        $customer->getColumn('name')->setLength(255);
        $customer->getColumn('timezone')->setLength(255);
    }
}
