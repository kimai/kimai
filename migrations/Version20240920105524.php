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
 * @version 2.22
 */
final class Version20240920105524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updates the user preferences for configurable work contract type';
    }

    public function up(Schema $schema): void
    {
        $ids = $this->connection->fetchFirstColumn("
SELECT DISTINCT user_id
FROM kimai2_user_preferences AS kp
WHERE kp.value > 0
  AND kp.name IN ('work_monday', 'work_tuesday', 'work_wednesday', 'work_thursday', 'work_friday', 'work_saturday', 'work_sunday')
  AND NOT EXISTS (
    SELECT 1
    FROM kimai2_user_preferences AS kp2
    WHERE kp2.user_id = kp.user_id
      AND kp2.name = 'work_contract_type'
);");

        foreach ($ids as $id) {
            $this->addSql('INSERT INTO kimai2_user_preferences (`user_id`, `name`, `value`) VALUES (:id, :name, :value)', [
                'id' => $id,
                'name' => 'work_contract_type',
                'value' => 'day',
            ]);
        }

        if (\count($ids) === 0) {
            $this->preventEmptyMigrationWarning();
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM kimai2_user_preferences WHERE `name` = 'work_contract_type'");
    }

    public function isTransactional(): bool
    {
        return true;
    }
}
