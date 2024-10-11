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
 * @version 2.0.20
 */
final class Version20230327143628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the working times table';
    }

    public function up(Schema $schema): void
    {
        $workingTimes = $schema->createTable('kimai2_working_times');

        $workingTimes->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $workingTimes->addColumn('user_id', 'integer', ['notnull' => true]);
        $workingTimes->addColumn('approved_by', 'integer', ['notnull' => false, 'default' => null]);
        $workingTimes->addColumn('date', 'date', ['notnull' => true]);
        $workingTimes->addColumn('expected', 'integer', ['notnull' => true]);
        $workingTimes->addColumn('actual', 'integer', ['notnull' => true]);
        $workingTimes->addColumn('approved_at', 'datetime', ['notnull' => false, 'default' => null]);
        $workingTimes->setPrimaryKey(['id']);
        $workingTimes->addIndex(['user_id'], 'IDX_F95E4933A76ED395');
        $workingTimes->addIndex(['approved_by'], 'IDX_F95E49334EA3CB3D');
        $workingTimes->addUniqueIndex(['user_id', 'date'], 'UNIQ_F95E4933A76ED395AA9E377A');
        $workingTimes->addForeignKeyConstraint('kimai2_users', ['user_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_F95E4933A76ED395');
        $workingTimes->addForeignKeyConstraint('kimai2_users', ['approved_by'], ['id'], ['onDelete' => 'SET NULL'], 'FK_F95E49334EA3CB3D');
    }

    public function down(Schema $schema): void
    {
        $workingTimes = $schema->getTable('kimai2_working_times');

        $workingTimes->removeForeignKey('FK_F95E4933A76ED395');
        $workingTimes->removeForeignKey('FK_F95E49334EA3CB3D');
        $schema->dropTable('kimai2_working_times');
    }
}
