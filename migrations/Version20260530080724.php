<?php

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
 * @version 2.59
 */
final class Version20260530080724 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the signature_date column to the user table';
    }

    public function up(Schema $schema): void
    {
        // a security related column
        if (!$schema->getTable('kimai2_users')->hasColumn('signature_date')) {
            $this->addSql('ALTER TABLE kimai2_users ADD signature_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }

        // improve session garbage collection
        if (!$schema->getTable('kimai2_sessions')->hasIndex('lifetime_idx')) {
            $this->addSql('CREATE INDEX lifetime_idx ON kimai2_sessions (lifetime)');
        }

        $this->preventEmptyMigrationWarning(false);
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('kimai2_users')->hasColumn('signature_date')) {
            $this->addSql('ALTER TABLE kimai2_users DROP signature_date');
        }

        if ($schema->getTable('kimai2_sessions')->hasIndex('lifetime_idx')) {
            $this->addSql('DROP INDEX lifetime_idx ON kimai2_sessions');
        }

        $this->preventEmptyMigrationWarning(false);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
