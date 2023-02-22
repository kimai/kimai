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
 * @version 2.0.2
 */
final class Version20230126002050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cleanup after 2.0';
    }

    public function up(Schema $schema): void
    {
        // make sure to take the 24-hour setting over by switching to britain format
        $this->addSql("UPDATE kimai2_user_preferences SET `value` = 'en_GB' WHERE `value` = 'en'");
        $this->addSql("UPDATE kimai2_user_preferences p0 LEFT JOIN kimai2_user_preferences p1 ON p0.user_id = p1.user_id SET p0.`value` = 'en' WHERE p0.`value` = 'en_GB' AND p0.`name` = 'language' AND p1.`value` = '0' AND p1.`name` = 'hours_24'");

        $this->addSql("DELETE FROM kimai2_user_preferences WHERE `name` = 'theme.collapsed_sidebar'");
        $this->addSql("DELETE FROM kimai2_user_preferences WHERE `name` = 'collapsed_sidebar'");
        $this->addSql("DELETE FROM kimai2_roles_permissions WHERE `permission` LIKE 'comments_create%'");

        $this->addSql("DELETE FROM kimai2_user_preferences where `name` = 'theme.layout'"); // cleanup for weird cases, was renamed to
        $this->addSql("DELETE FROM kimai2_user_preferences where `name` = 'theme_layout'");
        $this->addSql("DELETE FROM kimai2_user_preferences where `name` = 'layout'");

        $this->addSql("DELETE FROM kimai2_user_preferences where `name` = 'reporting.initial_view'");
        $this->addSql("DELETE FROM kimai2_user_preferences where `name` = 'hours_24'");
        $this->addSql("UPDATE kimai2_user_preferences SET `value` = 'default' WHERE `name` = 'skin' AND `value` NOT IN ('default', 'dark')");

        $this->addSql("DELETE FROM kimai2_configuration WHERE `name` = 'timesheet.active_entries.soft_limit'");
        $this->addSql("DELETE FROM kimai2_configuration WHERE `name` = 'theme.autocomplete_chars'");
        $this->addSql("DELETE FROM kimai2_configuration WHERE `name` = 'theme.tags_create'");
        $this->addSql("DELETE FROM kimai2_configuration WHERE `name` = 'theme.branding.mini'");
        $this->addSql("DELETE FROM kimai2_configuration WHERE `name` = 'theme.branding.title'");
        $this->addSql("UPDATE kimai2_configuration SET `value` = 'duration_fixed_begin' WHERE `name` = 'timesheet.mode' AND `value` = 'duration_only'");
        $this->addSql("UPDATE kimai2_configuration SET `value` = 'default' WHERE `name` = 'defaults.user.theme' AND `value` NOT IN ('default', 'dark')");

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

        $this->addSql("UPDATE kimai2_user_preferences SET `value` = 'fixed' WHERE `name` = 'layout' and `value` IN ('default', 'dark')");

        // rollback makes it impossible to choose the correct one
        $this->addSql("DELETE FROM kimai2_user_preferences WHERE `name` = 'skin'");
        $this->addSql("DELETE FROM kimai2_configuration WHERE `name` = 'defaults.user.theme'");
    }
}
