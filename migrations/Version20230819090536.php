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
 * @version 2.0.31
 */
final class Version20230819090536 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the supervisor columns to the user-table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_users');
        $table->addColumn('supervisor_id', 'integer', ['length' => 11, 'notnull' => false, 'default' => null]);
        $table->addForeignKeyConstraint('kimai2_users', ['supervisor_id'], ['id'], ['onDelete' => 'SET NULL'], 'FK_B9AC5BCE19E9AC5F');
        $table->addIndex(['supervisor_id'], 'IDX_B9AC5BCE19E9AC5F');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_users');

        $table->removeForeignKey('FK_B9AC5BCE19E9AC5F');
        $table->dropIndex('IDX_B9AC5BCE19E9AC5F');
        $table->dropColumn('supervisor_id');
    }
}
