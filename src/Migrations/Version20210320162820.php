<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Update invoice with columns: subtotal and payment date
 *
 * @version 1.14
 */
final class Version20210320162820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update invoice with columns: subtotal and payment date';
    }

    public function up(Schema $schema): void
    {
        $invoices = $schema->getTable('kimai2_invoices');
        $invoices->addColumn('payment_date', 'date', ['default' => null, 'notnull' => false]);
        $invoices->addColumn('subtotal', 'float', ['notnull' => true]);
    }

    public function down(Schema $schema): void
    {
        $invoices = $schema->getTable('kimai2_invoices');
        $invoices->dropColumn('payment_date');
        $invoices->dropColumn('subtotal');
    }
}
