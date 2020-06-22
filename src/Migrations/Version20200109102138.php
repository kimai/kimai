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
 * Creates comment tables for customers and projects
 *
 * @version 1.7
 */
final class Version20200109102138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates comment tables for customers and projects';
    }

    public function up(Schema $schema): void
    {
        $customerComment = $schema->createTable('kimai2_customers_comments');
        $customerComment->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $customerComment->addColumn('customer_id', 'integer', ['notnull' => true]);
        $customerComment->addColumn('message', 'text', ['notnull' => true]);
        $customerComment->addColumn('created_by_id', 'integer', ['notnull' => true]);
        $customerComment->addColumn('created_at', 'datetime', ['notnull' => true]);
        $customerComment->addColumn('pinned', 'boolean', ['notnull' => true, 'default' => false]);
        $customerComment->setPrimaryKey(['id']);
        $customerComment->addIndex(['customer_id'], 'IDX_A5B142D99395C3F3');
        $customerComment->addForeignKeyConstraint('kimai2_customers', ['customer_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_A5B142D99395C3F3');
        $customerComment->addForeignKeyConstraint('kimai2_users', ['created_by_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_A5B142D9B03A8386');

        $projectComment = $schema->createTable('kimai2_projects_comments');
        $projectComment->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $projectComment->addColumn('project_id', 'integer', ['notnull' => true]);
        $projectComment->addColumn('message', 'text', ['notnull' => true]);
        $projectComment->addColumn('created_by_id', 'integer', ['notnull' => true]);
        $projectComment->addColumn('created_at', 'datetime', ['notnull' => true]);
        $projectComment->addColumn('pinned', 'boolean', ['notnull' => true, 'default' => false]);
        $projectComment->setPrimaryKey(['id']);
        $projectComment->addIndex(['project_id'], 'IDX_29A23638166D1F9C');
        $projectComment->addForeignKeyConstraint('kimai2_projects', ['project_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_29A23638166D1F9C');
        $projectComment->addForeignKeyConstraint('kimai2_users', ['created_by_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_29A23638B03A8386');

        $this->addSql('DELETE from kimai2_configuration WHERE name = "theme.select_type"');
        $this->addSql('DELETE from kimai2_roles_permissions WHERE permission = "delete_other_profile"');
        $this->addSql('DELETE from kimai2_roles_permissions WHERE permission = "delete_own_profile"');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('kimai2_projects_comments');
        $schema->dropTable('kimai2_customers_comments');
    }
}
