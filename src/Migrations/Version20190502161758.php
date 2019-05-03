<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Doctrine\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20190502161758 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        if ($this->isPlatformSqlite()) {
            $this->addSql('ALTER TABLE kimai2_customers ADD COLUMN color VARCHAR(7) DEFAULT NULL');
            $this->addSql('ALTER TABLE kimai2_projects ADD COLUMN color VARCHAR(7) DEFAULT NULL');
            $this->addSql('ALTER TABLE kimai2_activities ADD COLUMN color VARCHAR(7) DEFAULT NULL');
        } else {
            $this->addSql('ALTER TABLE kimai2_activities ADD color VARCHAR(7) DEFAULT NULL');
            $this->addSql('ALTER TABLE kimai2_projects ADD color VARCHAR(7) DEFAULT NULL');
            $this->addSql('ALTER TABLE kimai2_customers ADD color VARCHAR(7) DEFAULT NULL');
        }
    }

    public function down(Schema $schema) : void
    {
        if ($this->isPlatformSqlite()) {
            // FIXME remove column in SQLite databases
        } else {
            $this->addSql('ALTER TABLE kimai2_activities DROP color');
            $this->addSql('ALTER TABLE kimai2_customers DROP color');
            $this->addSql('ALTER TABLE kimai2_projects DROP color');
        }
    }
}
