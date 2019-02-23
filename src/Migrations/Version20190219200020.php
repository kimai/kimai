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
 * Cleanup the user_preferences table from old configs.
 */
final class Version20190219200020 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $platform = $this->getPlatform();

        if (!in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }

        $userPrefs = $this->getTableName('user_preferences');

        $this->addSql('DELETE FROM ' . $userPrefs. ' WHERE name = "theme.fixed_layout"');
        $this->addSql('DELETE FROM ' . $userPrefs. ' WHERE name = "theme.boxed_layout"');
        $this->addSql('DELETE FROM ' . $userPrefs. ' WHERE name = "theme.mini_sidebar"');
    }

    public function down(Schema $schema): void
    {
    }
}
