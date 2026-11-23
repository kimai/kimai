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
 * @version 2.36.0
 */
final class Version20250608143244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the export template table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('kimai2_export_templates');

        $table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('title', 'string', ['notnull' => true, 'length' => 100]);
        $table->addColumn('renderer', 'string', ['notnull' => true, 'length' => 20]);
        $table->addColumn('language', 'string', ['notnull' => false, 'length' => 6]);
        $table->addColumn('columns', 'json', ['notnull' => true]);
        $table->addColumn('options', 'json', ['notnull' => true]);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['title'], 'UNIQ_2F0CA26F2B36786B');
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('kimai2_export_templates')) {
            $schema->dropTable('kimai2_export_templates');
        }
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
