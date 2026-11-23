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
 * @version 2.14
 */
final class Version20240214061246 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the table for API access tokens';
    }

    public function up(Schema $schema): void
    {
        $accessTokens = $schema->createTable('kimai2_access_token');

        $accessTokens->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $accessTokens->addColumn('user_id', 'integer', ['notnull' => true]);
        $accessTokens->addColumn('token', 'string', ['notnull' => true, 'length' => 100]);
        $accessTokens->addColumn('name', 'string', ['notnull' => true, 'length' => 50]);
        $accessTokens->addColumn('last_usage', 'datetime_immutable', ['notnull' => false, 'default' => null]);
        $accessTokens->addColumn('expires_at', 'datetime_immutable', ['notnull' => false, 'default' => null]);

        $accessTokens->setPrimaryKey(['id']);

        $accessTokens->addIndex(['user_id'], 'IDX_6FB0DB1EA76ED395');
        $accessTokens->addUniqueIndex(['token'], 'UNIQ_6FB0DB1E5F37A13B');

        $accessTokens->addForeignKeyConstraint('kimai2_users', ['user_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_6FB0DB1EA76ED395');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_access_token');
        $table->removeForeignKey('FK_6FB0DB1EA76ED395');

        $schema->dropTable('kimai2_access_token');
    }
}
