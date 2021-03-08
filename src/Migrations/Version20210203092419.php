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
 * Invoice improvements
 *
 * @version 1.14
 */
final class Version20210203092419 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link entities to invoices';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_timesheet ADD invoice_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_timesheet ADD CONSTRAINT FK_4F60C6B12989F1FD FOREIGN KEY (invoice_id) REFERENCES kimai2_invoices (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_4F60C6B12989F1FD ON kimai2_timesheet (invoice_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_timesheet DROP FOREIGN KEY FK_4F60C6B12989F1FD');
        $this->addSql('DROP INDEX IDX_4F60C6B12989F1FD ON kimai2_timesheet');
        $this->addSql('ALTER TABLE kimai2_timesheet DROP invoice_id');
    }
}
