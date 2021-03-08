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
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migrations fot the "delete user" feature.
 *
 * Adds constraints to the timesheet table, so all timesheet entries will be deleted when a user is deleted.
 */
final class Version20180730044139 extends AbstractMigration
{
    /**
     * @var Index[]
     */
    protected $indexesOld = [];

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     */
    public function up(Schema $schema): void
    {
        $timesheet = 'kimai2_timesheet';
        $user = 'kimai2_users';
        $activity = 'kimai2_activities';

        $this->addSql('ALTER TABLE ' . $timesheet . ' DROP FOREIGN KEY FK_4F60C6B18D93D649');
        $this->addSql('ALTER TABLE ' . $timesheet . ' ADD CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES ' . $user . ' (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     */
    public function down(Schema $schema): void
    {
        $timesheet = 'kimai2_timesheet';
        $user = 'kimai2_users';

        $this->addSql('ALTER TABLE ' . $timesheet . ' DROP FOREIGN KEY FK_4F60C6B18D93D649');
        $this->addSql('ALTER TABLE ' . $timesheet . ' ADD CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES ' . $user . ' (id)');
    }
}
