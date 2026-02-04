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
 * @version 2.x
 */
final class Version20260204185847 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add rate_factor_fixed_rate column to customer table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_customers ADD rate_factor_fixed_rate TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_customers DROP COLUMN rate_factor_fixed_rate');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
