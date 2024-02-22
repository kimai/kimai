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
 * @version 2.5.0
 */
final class Version20231130000719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix user preferences with dots in the name';
    }

    public function up(Schema $schema): void
    {
        // select u1.* from kimai2_user_preferences u1 where u1.name like '%.%' and exists(select 1 from kimai2_user_preferences u2 where u2.name = replace(u1.name, '.', '_') and u1.user_id = u2.user_id);
        // select u1.* from kimai2_user_preferences u1 where u1.name like '%.%';
        $this->addSql("delete u1 from kimai2_user_preferences u1 where u1.name like '%.%' and exists(select 1 from (SELECT name, user_id FROM kimai2_user_preferences WHERE name LIKE '%_%') u2 where u2.name = replace(u1.name, '.', '_') and u1.user_id = u2.user_id)");
        $this->addSql("update kimai2_user_preferences set `name` = replace(`name`, '.', '_')");
    }

    public function down(Schema $schema): void
    {
        $this->preventEmptyMigrationWarning();
    }
}
