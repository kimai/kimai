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
 * New Vat ID columns and invoice template improvements
 *
 * @version 1.6
 */
final class Version20191116110124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'New Vat ID columns and invoice template improvements';
    }

    protected function isSupportingForeignKeys(): bool
    {
        return false;
    }

    public function isTransactional(): bool
    {
        if ($this->isPlatformSqlite()) {
            // does fail if we use transactions, as tables are re-created and foreign keys would fail
            return false;
        }

        return true;
    }

    public function up(Schema $schema): void
    {
        $customers = $schema->getTable('kimai2_customers');
        $customers->addColumn('vat_id', 'string', ['length' => 50, 'notnull' => false]);

        $invoiceTemplates = $schema->getTable('kimai2_invoice_templates');
        $invoiceTemplates->addColumn('vat_id', 'string', ['length' => 50, 'notnull' => false, 'default' => null]);
        $invoiceTemplates->addColumn('contact', 'text', ['notnull' => false, 'default' => null]);
        $invoiceTemplates->addColumn('payment_details', 'text', ['notnull' => false, 'default' => null]);
    }

    public function down(Schema $schema): void
    {
        $invoiceTemplates = $schema->getTable('kimai2_invoice_templates');
        $invoiceTemplates->dropColumn('payment_details');
        $invoiceTemplates->dropColumn('contact');
        $invoiceTemplates->dropColumn('vat_id');

        $customers = $schema->getTable('kimai2_customers');
        $customers->dropColumn('vat_id');
    }
}
