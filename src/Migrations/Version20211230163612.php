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
 * @version 1.17
 */
final class Version20211230163612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the comment column to the invoices table.';
    }

    public function up(Schema $schema): void
    {
        $invoices = $schema->getTable('kimai2_invoices');
        $invoices->addColumn('comment', 'text', ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $invoices = $schema->getTable('kimai2_invoices');
        $invoices->dropColumn('comment');
    }
}
