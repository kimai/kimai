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
 * Migration for FOSUserBundle
 *
 * Changes the table structure of "users" table and migrates from json_array type to serialized array,
 * probably also fixing the higher required MariaDB version.
 *
 * This was fixed in earlier migrations for new installations, but it is still in here for users migrating up from a lower version.
 */
final class Version20180715160326 extends AbstractMigration
{
    /**
     * @var Index[]
     */
    protected $indexesOld = [];

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema): void
    {
        $users = 'kimai2_users';

        // delete all existing indexes
        $indexesOld = $schema->getTable($users)->getIndexes();
        foreach ($indexesOld as $index) {
            if (\in_array('name', $index->getColumns()) || \in_array('mail', $index->getColumns())) {
                $this->indexesOld[] = $index;
                $this->addSqlDropIndex($index->getName(), $users);
            }
        }

        if ($this->isPlatformSqlite()) {
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $users . ' AS SELECT id, name, mail, password, alias, active, registration_date, title, avatar, roles FROM ' . $users);
            $this->addSql('DROP TABLE ' . $users);
            $this->addSql('CREATE TABLE ' . $users . ' (id INTEGER NOT NULL, alias VARCHAR(60) DEFAULT NULL COLLATE BINARY, registration_date DATETIME DEFAULT NULL, title VARCHAR(50) DEFAULT NULL COLLATE BINARY, avatar VARCHAR(255) DEFAULT NULL COLLATE BINARY, enabled BOOLEAN NOT NULL, password VARCHAR(255) NOT NULL, roles CLOB NOT NULL --(DC2Type:array)
        , username VARCHAR(180) NOT NULL, username_canonical VARCHAR(180) NOT NULL, email VARCHAR(180) NOT NULL, email_canonical VARCHAR(180) NOT NULL, salt VARCHAR(255) DEFAULT NULL, last_login DATETIME DEFAULT NULL, confirmation_token VARCHAR(180) DEFAULT NULL, password_requested_at DATETIME DEFAULT NULL, PRIMARY KEY(id))');
            $this->addSql('INSERT INTO ' . $users . ' (id, username, username_canonical, email, email_canonical, password, alias, enabled, registration_date, title, avatar, roles) SELECT id, name, name, mail, mail, password, alias, active, registration_date, title, avatar, roles FROM __temp__' . $users);
            $this->addSql('DROP TABLE __temp__' . $users);
        } else {
            $this->addSql('ALTER TABLE ' . $users . ' CHANGE name username VARCHAR(180) NOT NULL, ADD username_canonical VARCHAR(180) NOT NULL, CHANGE mail email VARCHAR(180) NOT NULL, ADD email_canonical VARCHAR(180) NOT NULL, ADD salt VARCHAR(255) DEFAULT NULL, ADD last_login DATETIME DEFAULT NULL, ADD confirmation_token VARCHAR(180) DEFAULT NULL, ADD password_requested_at DATETIME DEFAULT NULL, CHANGE password password VARCHAR(255) NOT NULL, CHANGE alias alias VARCHAR(60) DEFAULT NULL, CHANGE registration_date registration_date DATETIME DEFAULT NULL, CHANGE title title VARCHAR(50) DEFAULT NULL, CHANGE avatar avatar VARCHAR(255) DEFAULT NULL, CHANGE roles roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', CHANGE active enabled TINYINT(1) NOT NULL');
            $this->addSql('UPDATE ' . $users . ' set username_canonical = username');
            $this->addSql('UPDATE ' . $users . ' set email_canonical = email');
        }

        $this->addSql('UPDATE ' . $users . ' SET roles = \'a:1:{i:0;s:16:"ROLE_SUPER_ADMIN";}\' WHERE roles LIKE "%ROLE_SUPER_ADMIN%"');
        $this->addSql('UPDATE ' . $users . ' SET roles = \'a:1:{i:0;s:10:"ROLE_ADMIN";}\' WHERE roles LIKE "%ROLE_ADMIN%"');
        $this->addSql('UPDATE ' . $users . ' SET roles = \'a:1:{i:0;s:13:"ROLE_TEAMLEAD";}\' WHERE roles LIKE "%ROLE_TEAMLEAD%"');
        $this->addSql('UPDATE ' . $users . ' SET roles = \'a:0:{}\' WHERE roles LIKE "%ROLE_USER%"');
        $this->addSql('UPDATE ' . $users . ' SET roles = \'a:1:{i:0;s:13:"ROLE_CUSTOMER";}\' WHERE roles LIKE "%ROLE_CUSTOMER%"');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCE92FC23A8 ON ' . $users . ' (username_canonical)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCEA0D96FBF ON ' . $users . ' (email_canonical)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCEC05FB297 ON ' . $users . ' (confirmation_token)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCEF85E0677 ON ' . $users . ' (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCEE7927C74 ON ' . $users . ' (email)');
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema): void
    {
        $users = 'kimai2_users';

        $indexToDelete = ['UNIQ_B9AC5BCE92FC23A8', 'UNIQ_B9AC5BCEA0D96FBF', 'UNIQ_B9AC5BCEC05FB297', 'UNIQ_B9AC5BCEF85E0677', 'UNIQ_B9AC5BCEE7927C74'];
        foreach ($indexToDelete as $index) {
            $this->addSqlDropIndex($index, $users);
        }

        if ($this->isPlatformSqlite()) {
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $users . ' AS SELECT id, username, email, enabled, password, roles, alias, registration_date, title, avatar FROM ' . $users);
            $this->addSql('DROP TABLE ' . $users);
            $this->addSql('CREATE TABLE ' . $users . ' (id INTEGER NOT NULL, alias VARCHAR(60) DEFAULT NULL, registration_date DATETIME DEFAULT NULL, title VARCHAR(50) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, active BOOLEAN NOT NULL, password VARCHAR(254) DEFAULT NULL COLLATE BINARY, roles CLOB NOT NULL COLLATE BINARY --(DC2Type:array)
            , name VARCHAR(60) NOT NULL COLLATE BINARY, mail VARCHAR(160) NOT NULL COLLATE BINARY, PRIMARY KEY(id))');
            $this->addSql('INSERT INTO ' . $users . ' (id, name, mail, active, password, roles, alias, registration_date, title, avatar) SELECT id, username, email, enabled, password, roles, alias, registration_date, title, avatar FROM __temp__' . $users);
            $this->addSql('DROP TABLE __temp__' . $users);
        } else {
            $this->addSql('ALTER TABLE ' . $users . ' CHANGE username name VARCHAR(60) NOT NULL COLLATE utf8mb4_unicode_ci, CHANGE email mail VARCHAR(160) NOT NULL COLLATE utf8mb4_unicode_ci, DROP username_canonical, DROP email_canonical, DROP salt, DROP last_login, DROP confirmation_token, DROP password_requested_at, CHANGE password password VARCHAR(254) DEFAULT NULL COLLATE utf8mb4_unicode_ci, CHANGE roles roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', CHANGE alias alias VARCHAR(60) DEFAULT NULL COLLATE utf8mb4_unicode_ci, CHANGE registration_date registration_date DATETIME DEFAULT NULL, CHANGE title title VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_unicode_ci, CHANGE avatar avatar VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, CHANGE enabled active TINYINT(1) NOT NULL');
        }

        $this->addSql('UPDATE ' . $users . ' SET roles = \'["ROLE_SUPER_ADMIN"]\' WHERE roles LIKE "%ROLE_SUPER_ADMIN%"');
        $this->addSql('UPDATE ' . $users . ' SET roles = \'["ROLE_ADMIN"]\' WHERE roles LIKE "%ROLE_ADMIN%"');
        $this->addSql('UPDATE ' . $users . ' SET roles = \'["ROLE_TEAMLEAD"]\' WHERE roles LIKE "%ROLE_TEAMLEAD%"');
        $this->addSql('UPDATE ' . $users . ' SET roles = \'["ROLE_USER"]\' WHERE roles LIKE "%ROLE_USER%"');
        $this->addSql('UPDATE ' . $users . ' SET roles = \'["ROLE_CUSTOMER"]\' WHERE roles LIKE "%ROLE_CUSTOMER%"');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCE5E237E06 ON ' . $users . ' (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCE5126AC48 ON ' . $users . ' (mail)');

        $usersTable = $schema->getTable($users);
        foreach ($this->indexesOld as $index) {
            $usersTable->addIndex($index->getColumns(), $index->getName(), $index->getFlags(), $index->getOptions());
        }
    }
}
