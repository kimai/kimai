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
 * @version 2.1.0
 *
 * FIXME - SEARCH FOR @2.1 TO FIND ALL CODE PIECES THAT CAN BE REMOVED IN COMBINATION WITH THIS MIGRATION!
 */
final class Version20993112235959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cleanup after 2.0, these changes will prevent rollbacks to 1.x';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM kimai2_user_preferences WHERE `name` = 'theme.collapsed_sidebar'");
        $this->addSql("DELETE FROM kimai2_user_preferences WHERE `name` = 'collapsed_sidebar'");
        $this->addSql("DELETE FROM kimai2_roles_permissions WHERE `permission` LIKE 'comments_create%'");

        $this->addSql("DELETE FROM kimai2_user_preferences where `name` = 'reporting.initial_view'");
        $this->addSql("DELETE FROM kimai2_user_preferences where `name` = 'hours_24'");
        $this->addSql("UPDATE kimai2_user_preferences SET `value` = 'default' WHERE `name` = 'layout' and `value` = 'fixed'");
        $this->addSql("UPDATE kimai2_user_preferences SET `value` = 'default' WHERE `name` = 'skin'");

        $this->addSql("DELETE FROM kimai2_configuration WHERE `name` = 'timesheet.active_entries.soft_limit'");
        $this->addSql("DELETE FROM kimai2_configuration WHERE `name` = 'theme.autocomplete_chars'");
        $this->addSql("DELETE FROM kimai2_configuration WHERE `name` = 'theme.tags_create'");
        $this->addSql("UPDATE kimai2_configuration SET `value` = 'default' WHERE `name` = 'timesheet.mode' and `value` = 'duration_only'");
        $this->addSql("UPDATE kimai2_configuration SET `value` = 'default' WHERE `name` = 'defaults.user.theme'");

        // TODO rename user configuration calendar first day agendaDay = day / agendaMonth = month

        $templates = $schema->getTable('kimai2_invoice_templates');
        $templates->dropColumn('decimal_duration');

        // cannot be moved to the earlier migration, because of the execution order of schema changes and SQL statements
        $templates->getColumn('language')->setNotnull(true);
    }

    public function down(Schema $schema): void
    {
        $templates = $schema->getTable('kimai2_invoice_templates');

        $templates->addColumn('decimal_duration', 'boolean', ['notnull' => true, 'default' => false]);
        $templates->getColumn('language')->setNotnull(false);

        $this->addSql("UPDATE kimai2_user_preferences SET `value` = 'fixed' WHERE `name` = 'layout' and `value` = 'default'");

        // rollback makes it impossible to choose the correct one
        $this->addSql("DELETE FROM kimai2_user_preferences WHERE `name` = 'skin'");
        $this->addSql("DELETE FROM kimai2_configuration WHERE `name` = 'defaults.user.theme'");
    }
}
