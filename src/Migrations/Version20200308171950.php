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
 * @version 1.9
 */
final class Version20200308171950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the invoice table';
    }

    public function up(Schema $schema): void
    {
        $invoices = $schema->createTable('kimai2_invoices');
        $invoices->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $invoices->addColumn('customer_id', 'integer', ['length' => 11, 'notnull' => true]);
        $invoices->addColumn('user_id', 'integer', ['length' => 11, 'notnull' => false]);
        $invoices->addColumn('created_at', 'datetime', ['notnull' => true]);
        $invoices->addColumn('timezone', 'string', ['length' => 64, 'notnull' => true]);
        $invoices->addColumn('status', 'string', ['length' => 20, 'notnull' => true]);
        $invoices->addColumn('invoice_filename', 'string', ['length' => 100, 'notnull' => true]);
        $invoices->addColumn('title', 'string', ['length' => 255, 'notnull' => true]);
        $invoices->addColumn('company', 'string', ['length' => 255, 'notnull' => true]);
        $invoices->addColumn('vat_id', 'string', ['length' => 50, 'notnull' => false, 'default' => null]);
        $invoices->addColumn('address', 'text', ['notnull' => false, 'default' => null]);
        $invoices->addColumn('contact', 'text', ['notnull' => false, 'default' => null]);
        $invoices->addColumn('due_days', 'integer', ['length' => 11, 'notnull' => true]);
        $invoices->addColumn('vat', 'float', ['notnull' => true]);
        $invoices->addColumn('calculator', 'string', ['length' => 20, 'notnull' => true]);
        $invoices->addColumn('number_generator', 'string', ['length' => 20, 'notnull' => true]);
        $invoices->addColumn('renderer', 'string', ['length' => 20, 'notnull' => true]);
        $invoices->addColumn('payment_terms', 'text', ['notnull' => false, 'default' => null]);
        $invoices->addColumn('payment_details', 'text', ['notnull' => false, 'default' => null]);
        $invoices->addColumn('decimal_duration', 'boolean', ['notnull' => true, 'default' => false]);
        $invoices->addColumn('language', 'string', ['notnull' => false, 'length' => 6, 'default' => null]);
        $invoices->addForeignKeyConstraint('kimai2_users', ['user_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_76C38E37A76ED395');
        $invoices->addForeignKeyConstraint('kimai2_customers', ['customer_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_76C38E379395C3F3');
        $invoices->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('kimai2_invoices');
    }
}
