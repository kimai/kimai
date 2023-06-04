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
 * Create the bookmark table, to store default search settings.
 *
 * @version 1.14
 */
final class Version20210316224358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the bookmark table';
    }

    public function up(Schema $schema): void
    {
        $bookmarks = $schema->createTable('kimai2_bookmarks');
        $bookmarks->addColumn('id', 'integer', ['length' => 11, 'autoincrement' => true, 'notnull' => true]);
        $bookmarks->addColumn('user_id', 'integer', ['length' => 11, 'notnull' => true]);
        $bookmarks->addColumn('type', 'string', ['length' => 20, 'notnull' => true]);
        $bookmarks->addColumn('name', 'string', ['length' => 50, 'notnull' => true]);
        $bookmarks->addColumn('content', 'text', ['notnull' => true]);
        $bookmarks->addForeignKeyConstraint('kimai2_users', ['user_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_4016EF25A76ED395');
        $bookmarks->addUniqueIndex(['user_id', 'name'], 'UNIQ_4016EF25A76ED3955E237E06');
        $bookmarks->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('kimai2_bookmarks');
    }
}
