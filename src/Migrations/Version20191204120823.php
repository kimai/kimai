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
 * Adds project_start and project_end to projects tables
 *
 * @version 1.7
 */
final class Version20191204120823 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds project_start and project_end to projects tables';
    }

    public function up(Schema $schema): void
    {
        $projects = $schema->getTable('kimai2_projects');
        $projects->addColumn('start', 'datetime', ['notnull' => false]);
        $projects->addColumn('end', 'datetime', ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $projects = $schema->getTable('kimai2_projects');
        $projects->dropColumn('end');
        $projects->dropColumn('start');
    }
}
