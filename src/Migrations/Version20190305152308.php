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
 * - rename mail to email in customer table
 * - converts all decimal to float values, as decimals are treated as string in PHP:
 *   https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#decimal
 *
 * @version 0.9
 */
final class Version20190305152308 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_activities CHANGE fixed_rate fixed_rate DOUBLE PRECISION DEFAULT NULL, CHANGE hourly_rate hourly_rate DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_customers CHANGE mail email VARCHAR(255) DEFAULT NULL, CHANGE fixed_rate fixed_rate DOUBLE PRECISION DEFAULT NULL, CHANGE hourly_rate hourly_rate DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_projects CHANGE budget budget DOUBLE PRECISION NOT NULL, CHANGE fixed_rate fixed_rate DOUBLE PRECISION DEFAULT NULL, CHANGE hourly_rate hourly_rate DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_timesheet CHANGE rate rate DOUBLE PRECISION NOT NULL, CHANGE fixed_rate fixed_rate DOUBLE PRECISION DEFAULT NULL, CHANGE hourly_rate hourly_rate DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_activities CHANGE fixed_rate fixed_rate NUMERIC(10, 2) DEFAULT NULL, CHANGE hourly_rate hourly_rate NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_customers CHANGE email mail VARCHAR(255) DEFAULT NULL, CHANGE fixed_rate fixed_rate NUMERIC(10, 2) DEFAULT NULL, CHANGE hourly_rate hourly_rate NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_projects CHANGE budget budget NUMERIC(10, 2) NOT NULL, CHANGE fixed_rate fixed_rate NUMERIC(10, 2) DEFAULT NULL, CHANGE hourly_rate hourly_rate NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_timesheet CHANGE rate rate NUMERIC(10, 2) NOT NULL, CHANGE fixed_rate fixed_rate NUMERIC(10, 2) DEFAULT NULL, CHANGE hourly_rate hourly_rate NUMERIC(10, 2) DEFAULT NULL');
    }
}
