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
 * Creates the color columns on: customer, project, activity.
 *
 * @version 1.0
 */
final class Version20190502161758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the color columns on: customer, project, activity';
    }

    public function up(Schema $schema): void
    {
        $customers = $schema->getTable('kimai2_customers');
        $customers->addColumn('color', 'string', ['length' => 7, 'notnull' => false, 'default' => null]);

        $projects = $schema->getTable('kimai2_projects');
        $projects->addColumn('color', 'string', ['length' => 7, 'notnull' => false, 'default' => null]);

        $activities = $schema->getTable('kimai2_activities');
        $activities->addColumn('color', 'string', ['length' => 7, 'notnull' => false, 'default' => null]);
    }

    public function down(Schema $schema): void
    {
        $customers = $schema->getTable('kimai2_customers');
        $customers->dropColumn('color');

        $projects = $schema->getTable('kimai2_projects');
        $projects->dropColumn('color');

        $activities = $schema->getTable('kimai2_activities');
        $activities->dropColumn('color');
    }
}
