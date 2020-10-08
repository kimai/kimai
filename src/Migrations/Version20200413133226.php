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
 * @version 1.9
 */
final class Version20200413133226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add color for tags';
    }

    public function up(Schema $schema): void
    {
        $tags = $schema->getTable('kimai2_tags');
        $tags->addColumn('color', 'string', ['length' => 7, 'notnull' => false, 'default' => null]);
    }

    public function down(Schema $schema): void
    {
        $tags = $schema->getTable('kimai2_tags');
        $tags->dropColumn('color');
    }
}
