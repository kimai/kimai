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
 * @version 2.00
 */
final class Version20993112235958 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Necessary changes for 2.0';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE kimai2_invoice_templates SET `language` = 'en' WHERE `language` IS NULL");

        //$this->addSql('UPDATE kimai2_user_preferences SET `value` = "boxed" WHERE `name` = "theme.layout"');
        //$this->addSql('UPDATE kimai2_user_preferences SET `value` = "default" WHERE `name` = "skin"');
        //$this->addSql('UPDATE kimai2_user_preferences SET `value` = "1" WHERE `name` = "theme.collapsed_sidebar"');
        //$this->addSql('UPDATE kimai2_configuration SET `value` = "default" WHERE `name` = "defaults.user.theme"');
        //$this->addSql('DELETE FROM kimai2_configuration WHERE `name` = "theme.autocomplete_chars"');
        //$this->addSql('DELETE FROM kimai2_configuration WHERE `name` = "theme.tags_create"');

        //$this->addSql("DELETE FROM kimai2_user_preferences where `name` = 'reporting.initial_view'");
        //$this->addSql("DELETE FROM kimai2_user_preferences where `name` = 'hours_24'");

        $this->addSql("UPDATE kimai2_user_preferences SET `name` = 'timesheet_daily_stats' WHERE `name` = 'timesheet.daily_stats'");
        $this->addSql("UPDATE kimai2_user_preferences SET `name` = 'collapsed_sidebar' WHERE `name` = 'theme.collapsed_sidebar'");
        $this->addSql("UPDATE kimai2_user_preferences SET `name` = 'layout' WHERE `name` = 'theme.layout'");
        $this->addSql("UPDATE kimai2_user_preferences SET `name` = 'login_initial_view' WHERE `name` = 'login.initial_view'");
        $this->addSql("UPDATE kimai2_user_preferences SET `name` = 'calendar_initial_view' WHERE `name` = 'calendar.initial_view'");
        $this->addSql("UPDATE kimai2_user_preferences SET `name` = 'export_decimal' WHERE `name` = 'timesheet.export_decimal'");
        $this->addSql("UPDATE kimai2_user_preferences SET `name` = 'update_browser_title' WHERE `name` = 'theme.update_browser_title'");

        $this->addSql('CREATE INDEX IDX_4F60C6B1415614018D93D649 ON kimai2_timesheet (end_time, user)');

        // TODO user configuration calendar first day agendaDay = day / agendaMonth = month

        // TODO delete timesheet.active_entries.soft_limit

        // TODO update SET `value` = '15' kimai2_configuration WHERE `name` = 'timesheet.time_increment' and value = '0'

        $this->addSql("UPDATE kimai2_configuration SET `value` = 'default' WHERE `name` = 'timesheet.mode' and `value` = 'duration_only'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_4F60C6B1415614018D93D649 ON kimai2_timesheet');

        $this->addSql("UPDATE kimai2_user_preferences SET `name` = 'theme.collapsed_sidebar' WHERE `name` = 'collapsed_sidebar'");
        $this->addSql("UPDATE kimai2_user_preferences SET `name` = 'theme.layout' WHERE `name` = 'theme_layout'");
        $this->addSql("UPDATE kimai2_user_preferences SET `name` = 'calendar.initial_view' WHERE `name` = 'calendar_initial_view'");
        $this->addSql("UPDATE kimai2_user_preferences SET `name` = 'login.initial_view' WHERE `name` = 'login_initial_view'");
        $this->addSql("UPDATE kimai2_user_preferences SET `name` = 'timesheet.daily_stats' WHERE `name` = 'timesheet_daily_stats'");
        $this->addSql("UPDATE kimai2_user_preferences SET `name` = 'timesheet.export_decimal' WHERE `name` = 'export_decimal'");
        $this->addSql("UPDATE kimai2_user_preferences SET `name` = 'theme.update_browser_title' WHERE `name` = 'update_browser_title'");

        $this->addSql('UPDATE kimai2_configuration SET `value` = null WHERE `name` = "defaults.user.theme"');
        $this->addSql('UPDATE kimai2_user_preferences SET `value` = "fixed" WHERE `name` = "theme.layout"');
        $this->addSql('UPDATE kimai2_user_preferences SET `value` = null WHERE `name` = "skin"');
        $this->addSql('UPDATE kimai2_user_preferences SET `value` = "0" WHERE `name` = "theme.collapsed_sidebar"');
    }
}
