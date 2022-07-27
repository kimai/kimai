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
 * @version 1.22.0
 */
final class Version20220722125847 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '(De-)Activate global activities for Projects';
    }

    public function up(Schema $schema): void
    {
        $projects = $schema->getTable('kimai2_projects');
        $projects->addColumn('global_activities', 'boolean', ['notnull' => true, 'default' => true]);
    }

    public function down(Schema $schema): void
    {
        $projects = $schema->getTable('kimai2_projects');
        $projects->dropColumn('global_activities');
    }
}
