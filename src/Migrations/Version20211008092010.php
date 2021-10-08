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
 * @version 1.15
 */
final class Version20211008092010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Extend field orderNumber from 20 to 50 characters.';
    }

    public function up(Schema $schema): void
    {
      $projects = $schema->getTable('kimai2_projects');
      $projects->changeColumn('order_number', ['length' => 50]);

    }

    public function down(Schema $schema): void
    {
      $projects = $schema->getTable('kimai2_projects');
      $projects->changeColumn('order_number', ['length' => 20]);
    }
}
