<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

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

    public function isTransactional(): bool
    {
        return false;
    }
}
