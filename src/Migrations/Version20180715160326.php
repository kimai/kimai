<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for FOSUserBundle
 *
 * Changes the table structure of "users" table and migrates from json_array type to serialized array,
 * probably also fixing the higher required MariaDB version.
 */
final class Version20180715160326 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();

        if ($this->connection->getDatabasePlatform()->getName() === 'sqlite') {
            $this->addSql('DROP INDEX UNIQ_B9AC5BCE5126AC48');
            $this->addSql('DROP INDEX UNIQ_B9AC5BCE5E237E06');
            $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_users AS SELECT id, name, mail, password, alias, active, registration_date, title, avatar, roles FROM kimai2_users');
            $this->addSql('DROP TABLE kimai2_users');
            $this->addSql('CREATE TABLE kimai2_users (id INTEGER NOT NULL, alias VARCHAR(60) DEFAULT NULL COLLATE BINARY, registration_date DATETIME DEFAULT NULL, title VARCHAR(50) DEFAULT NULL COLLATE BINARY, avatar VARCHAR(255) DEFAULT NULL COLLATE BINARY, enabled BOOLEAN NOT NULL, password VARCHAR(255) NOT NULL, roles CLOB NOT NULL --(DC2Type:array)
        , username VARCHAR(180) NOT NULL, username_canonical VARCHAR(180) NOT NULL, email VARCHAR(180) NOT NULL, email_canonical VARCHAR(180) NOT NULL, salt VARCHAR(255) DEFAULT NULL, last_login DATETIME DEFAULT NULL, confirmation_token VARCHAR(180) DEFAULT NULL, password_requested_at DATETIME DEFAULT NULL, PRIMARY KEY(id))');
            $this->addSql('INSERT INTO kimai2_users (id, username, username_canonical, email, email_canonical, password, alias, enabled, registration_date, title, avatar, roles) SELECT id, name, name, mail, mail, password, alias, active, registration_date, title, avatar, \'a:1:{i:0;s:16:"ROLE_SUPER_ADMIN";}\' FROM __temp__kimai2_users where roles like "%ROLE_SUPER_ADMIN%"');
            $this->addSql('INSERT INTO kimai2_users (id, username, username_canonical, email, email_canonical, password, alias, enabled, registration_date, title, avatar, roles) SELECT id, name, name, mail, mail, password, alias, active, registration_date, title, avatar, \'a:1:{i:0;s:10:"ROLE_ADMIN";}\' FROM __temp__kimai2_users where roles like "%ROLE_ADMIN%"');
            $this->addSql('INSERT INTO kimai2_users (id, username, username_canonical, email, email_canonical, password, alias, enabled, registration_date, title, avatar, roles) SELECT id, name, name, mail, mail, password, alias, active, registration_date, title, avatar, \'a:1:{i:0;s:13:"ROLE_TEAMLEAD";}\' FROM __temp__kimai2_users where roles like "%ROLE_TEAMLEAD%"');
            $this->addSql('INSERT INTO kimai2_users (id, username, username_canonical, email, email_canonical, password, alias, enabled, registration_date, title, avatar, roles) SELECT id, name, name, mail, mail, password, alias, active, registration_date, title, avatar, \'a:1:{i:0;s:9:"ROLE_USER";}\' FROM __temp__kimai2_users where roles like "%ROLE_USER%"');
            $this->addSql('INSERT INTO kimai2_users (id, username, username_canonical, email, email_canonical, password, alias, enabled, registration_date, title, avatar, roles) SELECT id, name, name, mail, mail, password, alias, active, registration_date, title, avatar, \'a:1:{i:0;s:13:"ROLE_CUSTOMER";}\' FROM __temp__kimai2_users where roles like "%ROLE_CUSTOMER%"');
            $this->addSql('DROP TABLE __temp__kimai2_users');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCE92FC23A8 ON kimai2_users (username_canonical)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCEA0D96FBF ON kimai2_users (email_canonical)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCEC05FB297 ON kimai2_users (confirmation_token)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCEF85E0677 ON kimai2_users (username)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCEE7927C74 ON kimai2_users (email)');
            $this->addSql('DROP INDEX UNIQ_8D08F631A76ED3955E237E06');
            $this->addSql('DROP INDEX IDX_8D08F631A76ED395');
        } elseif ($this->connection->getDatabasePlatform()->getName() === 'mysql') {
            $this->abortIf(true, 'MySQL cannot be migrated for now');
        } else {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }
    }

    public function down(Schema $schema) : void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();

        if ($this->connection->getDatabasePlatform()->getName() === 'sqlite') {
            $this->addSql('DROP INDEX UNIQ_B9AC5BCE92FC23A8');
            $this->addSql('DROP INDEX UNIQ_B9AC5BCEA0D96FBF');
            $this->addSql('DROP INDEX UNIQ_B9AC5BCEC05FB297');
            $this->addSql('DROP INDEX UNIQ_B9AC5BCEF85E0677');
            $this->addSql('DROP INDEX UNIQ_B9AC5BCEE7927C74');
            $this->addSql('CREATE TEMPORARY TABLE __temp__kimai2_users AS SELECT id, username, email, enabled, password, roles, alias, registration_date, title, avatar FROM kimai2_users');
            $this->addSql('DROP TABLE kimai2_users');
            $this->addSql('CREATE TABLE kimai2_users (id INTEGER NOT NULL, alias VARCHAR(60) DEFAULT NULL, registration_date DATETIME DEFAULT NULL, title VARCHAR(50) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, active BOOLEAN NOT NULL, password VARCHAR(254) DEFAULT NULL COLLATE BINARY, roles CLOB NOT NULL COLLATE BINARY --(DC2Type:json_array)
            , name VARCHAR(60) NOT NULL COLLATE BINARY, mail VARCHAR(160) NOT NULL COLLATE BINARY, PRIMARY KEY(id))');
            $this->addSql('INSERT INTO kimai2_users (id, name, mail, active, password, roles, alias, registration_date, title, avatar) SELECT id, username, email, enabled, password, \'["ROLE_SUPER_ADMIN"]\', alias, registration_date, title, avatar FROM __temp__kimai2_users where roles like "%ROLE_SUPER_ADMIN%"');
            $this->addSql('INSERT INTO kimai2_users (id, name, mail, active, password, roles, alias, registration_date, title, avatar) SELECT id, username, email, enabled, password, \'["ROLE_ADMIN"]\', alias, registration_date, title, avatar FROM __temp__kimai2_users where roles like "%ROLE_ADMIN%"');
            $this->addSql('INSERT INTO kimai2_users (id, name, mail, active, password, roles, alias, registration_date, title, avatar) SELECT id, username, email, enabled, password, \'["ROLE_TEAMLEAD"]\', alias, registration_date, title, avatar FROM __temp__kimai2_users where roles like "%ROLE_TEAMLEAD%"');
            $this->addSql('INSERT INTO kimai2_users (id, name, mail, active, password, roles, alias, registration_date, title, avatar) SELECT id, username, email, enabled, password, \'["ROLE_USER"]\', alias, registration_date, title, avatar FROM __temp__kimai2_users where roles like "%ROLE_USER%"');
            $this->addSql('INSERT INTO kimai2_users (id, name, mail, active, password, roles, alias, registration_date, title, avatar) SELECT id, username, email, enabled, password, \'["ROLE_CUSTOMER"]\', alias, registration_date, title, avatar FROM __temp__kimai2_users where roles like "%ROLE_CUSTOMER%"');
            $this->addSql('DROP TABLE __temp__kimai2_users');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCE5126AC48 ON kimai2_users (mail)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCE5E237E06 ON kimai2_users (name)');
        } elseif ($this->connection->getDatabasePlatform()->getName() === 'mysql') {
            $this->abortIf(true, 'MySQL cannot be migrated for now');
        } else {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }
    }
}
