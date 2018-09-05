<?php declare(strict_types=1);

namespace DoctrineMigrations;

use App\Doctrine\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adding hourly_rate and fixed_rate to:
 * - Activities
 * - Projects
 * - Customer
 */
final class Version20180905190737 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $platform = $this->getPlatform();

        if (!in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }

        $activity = $this->getTableName('activities');
        $project = $this->getTableName('projects');
        $customer = $this->getTableName('customers');

        $this->addSql('ALTER TABLE ' . $activity . ' ADD COLUMN fixed_rate NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $activity . ' ADD COLUMN hourly_rate NUMERIC(10, 2) DEFAULT NULL');

        $this->addSql('ALTER TABLE ' . $project . ' ADD COLUMN fixed_rate NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $project . ' ADD COLUMN hourly_rate NUMERIC(10, 2) DEFAULT NULL');

        $this->addSql('ALTER TABLE ' . $customer . ' ADD COLUMN fixed_rate NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $customer . ' ADD COLUMN hourly_rate NUMERIC(10, 2) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $platform = $this->getPlatform();

        if (!in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }

        $activity = $this->getTableName('activities');
        $project = $this->getTableName('projects');
        $customer = $this->getTableName('customers');

        if ($platform === 'sqlite') {
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $customer . ' AS SELECT id, name, number, comment, visible, company, contact, address, country, currency, phone, fax, mobile, mail, homepage, timezone FROM ' . $customer);
            $this->addSql('DROP TABLE ' . $customer);
            $this->addSql('CREATE TABLE ' . $customer . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, number VARCHAR(50) DEFAULT NULL, comment CLOB DEFAULT NULL, visible BOOLEAN NOT NULL, company VARCHAR(255) DEFAULT NULL, contact VARCHAR(255) DEFAULT NULL, address CLOB DEFAULT NULL, country VARCHAR(2) NOT NULL, currency VARCHAR(3) NOT NULL, phone VARCHAR(255) DEFAULT NULL, fax VARCHAR(255) DEFAULT NULL, mobile VARCHAR(255) DEFAULT NULL, mail VARCHAR(255) DEFAULT NULL, homepage VARCHAR(255) DEFAULT NULL, timezone VARCHAR(255) NOT NULL)');
            $this->addSql('INSERT INTO ' . $customer . ' (id, name, number, comment, visible, company, contact, address, country, currency, phone, fax, mobile, mail, homepage, timezone) SELECT id, name, number, comment, visible, company, contact, address, country, currency, phone, fax, mobile, mail, homepage, timezone FROM __temp__' . $customer);
            $this->addSql('DROP TABLE __temp__' . $customer);

            $this->addSql('DROP INDEX IDX_407F12069395C3F3');
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $project . ' AS SELECT id, customer_id, name, order_number, comment, visible, budget FROM ' . $project);
            $this->addSql('DROP TABLE ' . $project);
            $this->addSql('CREATE TABLE ' . $project . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, customer_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL, order_number CLOB DEFAULT NULL, comment CLOB DEFAULT NULL, visible BOOLEAN NOT NULL, budget NUMERIC(10, 2) NOT NULL)');
            $this->addSql('INSERT INTO ' . $project . ' (id, customer_id, name, order_number, comment, visible, budget) SELECT id, customer_id, name, order_number, comment, visible, budget FROM __temp__' . $project);
            $this->addSql('DROP TABLE __temp__' . $project);
            $this->addSql('CREATE INDEX IDX_407F12069395C3F3 ON ' . $project . ' (customer_id)');

            $this->addSql('DROP INDEX IDX_8811FE1C166D1F9C');
            $this->addSql('CREATE TEMPORARY TABLE __temp__' . $activity . ' AS SELECT id, project_id, name, comment, visible FROM ' . $activity);
            $this->addSql('DROP TABLE ' . $activity);
            $this->addSql('CREATE TABLE ' . $activity . ' (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, project_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL, comment CLOB DEFAULT NULL, visible BOOLEAN NOT NULL)');
            $this->addSql('INSERT INTO ' . $activity . ' (id, project_id, name, comment, visible) SELECT id, project_id, name, comment, visible FROM __temp__' . $activity);
            $this->addSql('DROP TABLE __temp__' . $activity);
            $this->addSql('CREATE INDEX IDX_8811FE1C166D1F9C ON ' . $activity . ' (project_id)');
        } else {
            $this->addSql('ALTER TABLE ' . $customer . ' DROP hourly_rate');
            $this->addSql('ALTER TABLE ' . $customer . ' DROP fixed_rate');
            $this->addSql('ALTER TABLE ' . $project . ' DROP hourly_rate');
            $this->addSql('ALTER TABLE ' . $project . ' DROP fixed_rate');
            $this->addSql('ALTER TABLE ' . $activity . ' DROP hourly_rate');
            $this->addSql('ALTER TABLE ' . $activity . ' DROP fixed_rate');
        }
    }
}
