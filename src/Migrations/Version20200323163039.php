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
final class Version20200323163039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set internal-rate from rate for existing timesheet entries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE kimai2_timesheet SET internal_rate = rate');
    }

    public function down(Schema $schema): void
    {
    }
}
