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
 * @version 2.12
 */
final class Version20240214061246 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the table for API access tokens';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE kimai2_access_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(100) NOT NULL, name VARCHAR(50) NOT NULL, last_usage DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6FB0DB1EA76ED395 (user_id), UNIQUE INDEX UNIQ_6FB0DB1E5F37A13B (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE kimai2_access_token ADD CONSTRAINT FK_6FB0DB1EA76ED395 FOREIGN KEY (user_id) REFERENCES kimai2_users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_access_token DROP FOREIGN KEY FK_6FB0DB1EA76ED395');
        $this->addSql('DROP TABLE kimai2_access_token');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
