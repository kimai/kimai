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
use Doctrine\DBAL\Types\Types;

/**
 * @version 2.52
 */
final class Version20260304231806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add invoice email address to customer';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_customers');
        $table->addColumn('invoice_email', Types::STRING, ['length' => 75, 'notnull' => false, 'default' => null]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_customers');
        $table->dropColumn('invoice_email');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
