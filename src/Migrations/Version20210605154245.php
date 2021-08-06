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
final class Version20210605154245 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cleans up the user table';
    }

    public function up(Schema $schema): void
    {
        $user = $schema->getTable('kimai2_users');
        $user->dropIndex('UNIQ_B9AC5BCE92FC23A8');
        $user->dropIndex('UNIQ_B9AC5BCEA0D96FBF');
        $user->dropColumn('username_canonical');
        $user->dropColumn('email_canonical');
        $user->dropColumn('salt');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_users ADD username_canonical VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD email_canonical VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD salt VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCE92FC23A8 ON kimai2_users (username_canonical)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCEA0D96FBF ON kimai2_users (email_canonical)');
    }
}
