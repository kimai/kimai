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
 * Create the system configuration table.
 *
 * @version 0.9
 */
final class Version20190321181243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->isPlatformSqlite()) {
            $this->addSql('CREATE TABLE kimai2_configuration (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, value VARCHAR(255) DEFAULT NULL)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_1C5D63D85E237E06 ON kimai2_configuration (name)');
        } else {
            $this->addSql('CREATE TABLE kimai2_configuration (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, value VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_1C5D63D85E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        }
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('kimai2_configuration');
    }
}
