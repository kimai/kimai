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
 * @version 2.41
 */
final class Version20251031143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the invoice template meta table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE kimai2_invoice_templates_meta (id INT AUTO_INCREMENT NOT NULL, template_id INT NOT NULL, name VARCHAR(50) NOT NULL, value TEXT DEFAULT NULL, visible TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_A165B0555DA0FB8 (template_id), UNIQUE INDEX UNIQ_A165B0555DA0FB85E237E06 (template_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE kimai2_invoice_templates_meta ADD CONSTRAINT FK_A165B0555DA0FB8 FOREIGN KEY (template_id) REFERENCES kimai2_invoice_templates (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_invoice_templates_meta');
        $table->removeForeignKey('FK_A165B0555DA0FB8');

        $schema->dropTable('kimai2_invoice_templates_meta');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
