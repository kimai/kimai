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
 * @version 2.x
 */
final class Version20260621144402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add language to customer';
    }

    public function up(Schema $schema): void
    {
        // Use the language of the user with the lowest user_id (mostly the admin)
        $language = $this->connection->fetchOne("
SELECT `value`
FROM kimai2_user_preferences
WHERE `name` = 'language'
ORDER BY user_id ASC
LIMIT 1
");

        if ($language === false) {
            $language = 'en';
        }

        $this->addSql('ALTER TABLE kimai2_customers ADD language VARCHAR(6) DEFAULT NULL');
        $this->addSql('UPDATE kimai2_customers SET language = ?', [$language]);
        $this->addSql('INSERT INTO kimai2_configuration (`name`, `value`) VALUES (?, ?)', ['defaults.customer.language', $language]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM kimai2_configuration WHERE `name` = ?', ['defaults.customer.language']);
        $this->addSql('ALTER TABLE kimai2_customers DROP COLUMN language');
    }
}
