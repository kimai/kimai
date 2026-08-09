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
use Doctrine\DBAL\Types\Types;

/**
 * @version 2.64
 */
final class Version20260803155144 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add configurable scopes to API access tokens';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_access_token');

        if (!$table->hasColumn('scopes')) {
            // nullable: existing tokens keep "scopes = NULL" and run with full permissions (backward compatibility)
            $schema->getTable('kimai2_access_token')->addColumn('scopes', Types::JSON, ['notnull' => false, 'default' => null]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_access_token');

        if ($table->hasColumn('scopes')) {
            $schema->getTable('kimai2_access_token')->dropColumn('scopes');
        }
    }
}
