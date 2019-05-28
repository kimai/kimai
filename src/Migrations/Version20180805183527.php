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
 * Added "API-token" to users table.
 */
final class Version20180805183527 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     */
    public function up(Schema $schema): void
    {
        $platform = $this->getPlatform();

        if (!in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }

        $user = $this->getTableName('users');

        if ($platform === 'sqlite') {
            $this->addSql('ALTER TABLE ' . $user . ' ADD COLUMN api_token VARCHAR(255) DEFAULT NULL');
        } else {
            $this->addSql('ALTER TABLE ' . $user . ' ADD api_token VARCHAR(255) DEFAULT NULL');
        }
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     */
    public function down(Schema $schema): void
    {
        $platform = $this->getPlatform();

        if (!in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }

        $user = $this->getTableName('users');

        if ($platform === 'sqlite') {
            $this->addSql('DROP INDEX UNIQ_B9AC5BCE92FC23A8');
            $this->addSql('DROP INDEX UNIQ_B9AC5BCEA0D96FBF');
            $this->addSql('DROP INDEX UNIQ_B9AC5BCEC05FB297');
            $this->addSql('DROP INDEX UNIQ_B9AC5BCEF85E0677');
            $this->addSql('DROP INDEX UNIQ_B9AC5BCEE7927C74');
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $user . ' AS SELECT id, username, username_canonical, email, email_canonical, enabled, salt, password, last_login, confirmation_token, password_requested_at, roles, alias, registration_date, title, avatar FROM ' . $user);
            $this->addSql('DROP TABLE ' . $user);
            $this->addSql('CREATE TABLE ' . $user . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, username VARCHAR(180) NOT NULL, username_canonical VARCHAR(180) NOT NULL, email VARCHAR(180) NOT NULL, email_canonical VARCHAR(180) NOT NULL, enabled BOOLEAN NOT NULL, salt VARCHAR(255) DEFAULT NULL, password VARCHAR(255) NOT NULL, last_login DATETIME DEFAULT NULL, confirmation_token VARCHAR(180) DEFAULT NULL, password_requested_at DATETIME DEFAULT NULL, roles CLOB NOT NULL, alias VARCHAR(60) DEFAULT NULL, registration_date DATETIME DEFAULT NULL, title VARCHAR(50) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL)');
            $this->addSql('INSERT INTO ' . $user . ' (id, username, username_canonical, email, email_canonical, enabled, salt, password, last_login, confirmation_token, password_requested_at, roles, alias, registration_date, title, avatar) SELECT id, username, username_canonical, email, email_canonical, enabled, salt, password, last_login, confirmation_token, password_requested_at, roles, alias, registration_date, title, avatar FROM __temp__' . $user);
            $this->addSql('DROP TABLE __temp__' . $user);
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCE92FC23A8 ON ' . $user . ' (username_canonical)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCEA0D96FBF ON ' . $user . ' (email_canonical)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCEC05FB297 ON ' . $user . ' (confirmation_token)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCEF85E0677 ON ' . $user . ' (username)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCEE7927C74 ON ' . $user . ' (email)');
        } else {
            $this->addSql('ALTER TABLE ' . $user . ' DROP api_token');
        }
    }
}
