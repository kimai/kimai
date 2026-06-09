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
 * @version 3.0
 */
final class Version20990101010001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the messenger transport table';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('messenger_messages')) {
            $this->preventEmptyMigrationWarning();

            return;
        }

        $table = $schema->createTable('messenger_messages');

        $table->addColumn('id', 'bigint', ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('body', 'text', ['notnull' => true]);
        $table->addColumn('headers', 'text', ['notnull' => true]);
        $table->addColumn('queue_name', 'string', ['length' => 190, 'notnull' => true]);
        $table->addColumn('created_at', 'datetime_immutable', ['notnull' => true]);
        $table->addColumn('available_at', 'datetime_immutable', ['notnull' => true]);
        $table->addColumn('delivered_at', 'datetime_immutable', ['notnull' => false, 'default' => null]);

        $this->addPrimaryKeyConstraint($table, ['id']);
        $table->addIndex(['queue_name', 'available_at', 'delivered_at', 'id'], 'IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('messenger_messages')) {
            $this->preventEmptyMigrationWarning();

            return;
        }

        $schema->dropTable('messenger_messages');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
