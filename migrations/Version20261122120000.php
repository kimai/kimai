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
 * The financial year was removed, reports and widgets always use the calendar year.
 *
 * @version 2.73.0
 */
final class Version20261122120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove the financial year configuration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM kimai2_configuration WHERE `name` = ?', ['company.financial_year']);
    }

    public function down(Schema $schema): void
    {
        // the setting was removed, there is nothing to restore
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
