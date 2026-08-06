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
 * @version 2.63
 */
final class Version20260802144517 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Increases language column length';
    }

    public function up(Schema $schema): void
    {
        $schema->getTable('kimai2_export_templates')->getColumn('language')->setLength(35);
        $schema->getTable('kimai2_invoice_templates')->getColumn('language')->setLength(35);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('kimai2_export_templates')->getColumn('language')->setLength(6);
        $schema->getTable('kimai2_invoice_templates')->getColumn('language')->setLength(6);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
