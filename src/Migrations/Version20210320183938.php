<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates meta table to store custom fields for invoice entities
 *
 * @version 1.14
 */
final class Version20210320183938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates meta table to store custom fields for invoice entities';
    }

    public function up(Schema $schema): void
    {
        $invoiceMeta = $schema->createTable('kimai2_invoices_meta');
        $invoiceMeta->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $invoiceMeta->addColumn('invoice_id', 'integer', ['notnull' => true]);
        $invoiceMeta->addColumn('name', 'string', ['notnull' => true, 'length' => 50]);
        $invoiceMeta->addColumn('value', 'string', ['notnull' => false, 'length' => 255]);
        $invoiceMeta->addColumn('visible', 'boolean', ['notnull' => false, 'default' => false]);
        $invoiceMeta->setPrimaryKey(['id']);
        $invoiceMeta->addIndex(['invoice_id']);
        $invoiceMeta->addUniqueIndex(['invoice_id', 'name']);
        $invoiceMeta->addForeignKeyConstraint('kimai2_invoices', ['invoice_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('kimai2_invoices_meta');
    }
}
