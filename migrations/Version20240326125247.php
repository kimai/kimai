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
 * @version 2.14
 */
final class Version20240326125247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the number columns to activity and project';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_activities ADD number VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_projects ADD number VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_projects DROP number');
        $this->addSql('ALTER TABLE kimai2_activities DROP number');
    }
}
