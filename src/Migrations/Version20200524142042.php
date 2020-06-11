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
 * @version 1.10
 */
final class Version20200524142042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add table to store sessions in database';
    }

    public function up(Schema $schema): void
    {
        $sessions = $schema->createTable('kimai2_sessions');
        $sessions->addColumn('id', 'string', ['length' => 128, 'notnull' => true]);
        $sessions->addColumn('data', 'blob', ['length' => 65535, 'notnull' => true]);
        $sessions->addColumn('time', 'integer', ['unsigned' => true, 'notnull' => true]);
        $sessions->addColumn('lifetime', 'integer', ['unsigned' => true, 'notnull' => true]);
        $sessions->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('kimai2_sessions');
    }
}
