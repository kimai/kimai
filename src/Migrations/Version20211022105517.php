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
 * @version 2.0
 */
final class Version20211022105517 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updates Kimai to version 2.0';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE kimai2_user_preferences SET `value` = "boxed" WHERE `name` = "theme.layout"');
        $this->addSql('UPDATE kimai2_user_preferences SET `value` = "default" WHERE `name` = "skin"');
        $this->addSql('UPDATE kimai2_user_preferences SET `value` = "1" WHERE `name` = "theme.collapsed_sidebar"');
        $this->addSql('UPDATE kimai2_configuration SET `value` = "default" WHERE `name` = "defaults.user.theme"');
        $this->addSql('DELETE FROM kimai2_configuration WHERE `name` = "theme.autocomplete_chars"');
        $this->addSql('DELETE FROM kimai2_configuration WHERE `name` = "theme.tags_create"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE kimai2_configuration SET `value` = null WHERE `name` = "defaults.user.theme"');
        $this->addSql('UPDATE kimai2_user_preferences SET `value` = "fixed" WHERE `name` = "theme.layout"');
        $this->addSql('UPDATE kimai2_user_preferences SET `value` = null WHERE `name` = "skin"');
        $this->addSql('UPDATE kimai2_user_preferences SET `value` = "0" WHERE `name` = "theme.collapsed_sidebar"');
    }
}
