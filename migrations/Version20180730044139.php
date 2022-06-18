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
     * @throws \Doctrine\DBAL\Exception
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_timesheet DROP FOREIGN KEY FK_4F60C6B18D93D649');
        $this->addSql('ALTER TABLE kimai2_timesheet ADD CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES kimai2_users (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Exception
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_timesheet DROP FOREIGN KEY FK_4F60C6B18D93D649');
        $this->addSql('ALTER TABLE kimai2_timesheet ADD CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES kimai2_users (id)');
    }
}
