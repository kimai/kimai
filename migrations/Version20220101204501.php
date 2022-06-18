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

final class Version20220101204501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the invoice-meta table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('kimai2_invoices_meta');
        $table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('invoice_id', 'integer', ['notnull' => true]);
        $table->addColumn('name', 'string', ['notnull' => true, 'length' => 50]);
        $table->addColumn('value', 'text', ['notnull' => false, 'length' => 65535]);
        $table->addColumn('visible', 'boolean', ['notnull' => true, 'default' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['invoice_id'], 'IDX_7EDC37D92989F1FD');
        $table->addUniqueIndex(['invoice_id', 'name'], 'UNIQ_7EDC37D92989F1FD5E237E06');
        $table->addForeignKeyConstraint('kimai2_invoices', ['invoice_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_7EDC37D92989F1FD');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('kimai2_invoices_meta');
    }
}
