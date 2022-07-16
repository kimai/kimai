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
 * @version 2.1.0
 */
final class Version20993112235959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cleanup after 2.0, these changes will prevent rollbacks to 1.x';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM kimai2_roles_permissions WHERE `permission` LIKE 'comments_create%'");

        $schema->getTable('kimai2_invoice_templates')->getColumn('language')->setNotnull(true);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('kimai2_invoice_templates')->getColumn('language')->setNotnull(false);
    }
}
