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
 * @version 1.15
 */
final class Version20210802174320 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove teamlead id from teams table';
    }

    public function up(Schema $schema): void
    {
        $teams = $schema->getTable('kimai2_teams');
        // @see https://github.com/kimai/kimai/issues/2706
        if ($teams->hasForeignKey('FK_3BEDDC7F8F7DE5D7')) {
            $teams->removeForeignKey('FK_3BEDDC7F8F7DE5D7');
        }
        $teams->dropColumn('teamlead_id');
    }

    public function down(Schema $schema): void
    {
        $teams = $schema->getTable('kimai2_teams');
        $teams->addColumn('teamlead_id', 'integer', ['length' => 11, 'notnull' => false]);
    }
}
