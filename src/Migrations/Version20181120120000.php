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
 * Add the Google authentication secret code to a user for 2fa.
 * Bundle: https://github.com/scheb/two-factor-bundle
 */
final class Version20181120120000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $platform = $this->getPlatform();

        if (!in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }

        if ($platform === 'sqlite') {
            $this->addSql('ALTER TABLE ' . $this->getTableName('users') . ' ADD googleAuthenticatorSecret VARCHAR(10)');
        } else {
            $this->addSql('ALTER TABLE ' . $this->getTableName('users') . ' ADD COLUMN googleAuthenticatorSecret VARCHAR(10)');
        }
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $platform = $this->getPlatform();

        if (!in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }

        if ($platform === 'mysql') {
            $this->addSql('ALTER TABLE ' . $this->getTableName('users') . ' DROP googleAuthenticatorSecret');
        }
    }
}
