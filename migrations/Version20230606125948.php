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
 * @version 2.0.26
 */
final class Version20230606125948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the visible column for tags';
    }

    public function up(Schema $schema): void
    {
        $tags = $schema->getTable('kimai2_tags');
        $tags->addColumn('visible', 'boolean', ['notnull' => false, 'default' => true]);
    }

    public function down(Schema $schema): void
    {
        $tags = $schema->getTable('kimai2_tags');
        $tags->dropColumn('visible');
    }
}
