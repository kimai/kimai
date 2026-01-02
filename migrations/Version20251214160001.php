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
 * @version 2.45
 */
final class Version20251214160001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index for a query called on every timesheet page';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_tags');

        if (!$table->hasIndex('IDX_27CAF54C7AB0E859')) {
            // used to count the tags for the dropdown (filter and timesheet edit)
            $table->addIndex(['visible'], 'IDX_27CAF54C7AB0E859');
        }
        // deleted in this release
        $this->addSql("DELETE from kimai2_configuration where name = 'defaults.user.language'");
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_tags');

        if ($table->hasIndex('IDX_27CAF54C7AB0E859')) {
            $table->dropIndex('IDX_27CAF54C7AB0E859');
        }
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
