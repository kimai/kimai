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
 * Initial database structure of Kimai 2.
 * This file is only required for testing the migrations!
 */
final class Version20180701120000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $platform = $this->getPlatform();

        if (!in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }

        if ($platform === 'sqlite') {
            $this->addSql('CREATE TABLE kimai2_users (id INTEGER NOT NULL, name VARCHAR(60) NOT NULL, mail VARCHAR(160) NOT NULL, password VARCHAR(254) DEFAULT NULL, alias VARCHAR(60) DEFAULT NULL, active BOOLEAN NOT NULL, registration_date DATETIME DEFAULT NULL, title VARCHAR(50) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, roles CLOB NOT NULL --(DC2Type:json_array)
        , PRIMARY KEY(id))');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCE5E237E06 ON kimai2_users (name)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B9AC5BCE5126AC48 ON kimai2_users (mail)');
            $this->addSql('CREATE TABLE kimai2_user_preferences (id INTEGER NOT NULL, user_id INTEGER DEFAULT NULL, name VARCHAR(50) NOT NULL, value VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IDX_8D08F631A76ED395 ON kimai2_user_preferences (user_id)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_8D08F631A76ED3955E237E06 ON kimai2_user_preferences (user_id, name)');
            $this->addSql('CREATE TABLE kimai2_customers (id INTEGER NOT NULL, name VARCHAR(255) NOT NULL, number VARCHAR(50) DEFAULT NULL, comment CLOB DEFAULT NULL, visible BOOLEAN NOT NULL, company VARCHAR(255) DEFAULT NULL, contact VARCHAR(255) DEFAULT NULL, address CLOB DEFAULT NULL, country VARCHAR(2) NOT NULL, currency VARCHAR(3) NOT NULL, phone VARCHAR(255) DEFAULT NULL, fax VARCHAR(255) DEFAULT NULL, mobile VARCHAR(255) DEFAULT NULL, mail VARCHAR(255) DEFAULT NULL, homepage VARCHAR(255) DEFAULT NULL, timezone VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE TABLE kimai2_projects (id INTEGER NOT NULL, customer_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL, order_number CLOB DEFAULT NULL, comment CLOB DEFAULT NULL, visible BOOLEAN NOT NULL, budget NUMERIC(10, 2) NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IDX_407F12069395C3F3 ON kimai2_projects (customer_id)');
            $this->addSql('CREATE TABLE kimai2_activities (id INTEGER NOT NULL, project_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL, comment CLOB DEFAULT NULL, visible BOOLEAN NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IDX_8811FE1C166D1F9C ON kimai2_activities (project_id)');
            $this->addSql('CREATE TABLE kimai2_timesheet (id INTEGER NOT NULL, user INTEGER DEFAULT NULL, activity_id INTEGER DEFAULT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, duration INTEGER DEFAULT NULL, description CLOB DEFAULT NULL, rate NUMERIC(10, 2) NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IDX_4F60C6B18D93D649 ON kimai2_timesheet (user)');
            $this->addSql('CREATE INDEX IDX_4F60C6B181C06096 ON kimai2_timesheet (activity_id)');
            $this->addSql('CREATE TABLE kimai2_invoice_templates (id INTEGER NOT NULL, name VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, company VARCHAR(255) NOT NULL, address CLOB DEFAULT NULL, due_days INTEGER NOT NULL, vat INTEGER DEFAULT NULL, calculator VARCHAR(20) NOT NULL, number_generator VARCHAR(20) NOT NULL, renderer VARCHAR(20) NOT NULL, payment_terms CLOB DEFAULT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_1626CFE95E237E06 ON kimai2_invoice_templates (name)');
        } else {
            $this->addSql('CREATE TABLE kimai2_users (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(60) NOT NULL, mail VARCHAR(160) NOT NULL, password VARCHAR(254) DEFAULT NULL, alias VARCHAR(60) DEFAULT NULL, active TINYINT(1) NOT NULL, registration_date DATETIME DEFAULT NULL, title VARCHAR(50) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json_array)\', UNIQUE INDEX UNIQ_B9AC5BCE5E237E06 (name), UNIQUE INDEX UNIQ_B9AC5BCE5126AC48 (mail), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('CREATE TABLE kimai2_user_preferences (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(50) NOT NULL, value VARCHAR(255) DEFAULT NULL, INDEX IDX_8D08F631A76ED395 (user_id), UNIQUE INDEX UNIQ_8D08F631A76ED3955E237E06 (user_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('CREATE TABLE kimai2_customers (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, number VARCHAR(50) DEFAULT NULL, comment TEXT DEFAULT NULL, visible TINYINT(1) NOT NULL, company VARCHAR(255) DEFAULT NULL, contact VARCHAR(255) DEFAULT NULL, address TEXT DEFAULT NULL, country VARCHAR(2) NOT NULL, currency VARCHAR(3) NOT NULL, phone VARCHAR(255) DEFAULT NULL, fax VARCHAR(255) DEFAULT NULL, mobile VARCHAR(255) DEFAULT NULL, mail VARCHAR(255) DEFAULT NULL, homepage VARCHAR(255) DEFAULT NULL, timezone VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('CREATE TABLE kimai2_projects (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, order_number TINYTEXT DEFAULT NULL, comment TEXT DEFAULT NULL, visible TINYINT(1) NOT NULL, budget NUMERIC(10, 2) NOT NULL, INDEX IDX_407F12069395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('CREATE TABLE kimai2_activities (id INT AUTO_INCREMENT NOT NULL, project_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, comment TEXT DEFAULT NULL, visible TINYINT(1) NOT NULL, INDEX IDX_8811FE1C166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('CREATE TABLE kimai2_timesheet (id INT AUTO_INCREMENT NOT NULL, user INT DEFAULT NULL, activity_id INT DEFAULT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, duration INT DEFAULT NULL, description TEXT DEFAULT NULL, rate NUMERIC(10, 2) NOT NULL, INDEX IDX_4F60C6B18D93D649 (user), INDEX IDX_4F60C6B181C06096 (activity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('CREATE TABLE kimai2_invoice_templates (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, company VARCHAR(255) NOT NULL, address TEXT DEFAULT NULL, due_days INT NOT NULL, vat INT DEFAULT NULL, calculator VARCHAR(20) NOT NULL, number_generator VARCHAR(20) NOT NULL, renderer VARCHAR(20) NOT NULL, payment_terms TEXT DEFAULT NULL, UNIQUE INDEX UNIQ_1626CFE95E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('ALTER TABLE kimai2_user_preferences ADD CONSTRAINT FK_8D08F631A76ED395 FOREIGN KEY (user_id) REFERENCES kimai2_users (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE kimai2_projects ADD CONSTRAINT FK_407F12069395C3F3 FOREIGN KEY (customer_id) REFERENCES kimai2_customers (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE kimai2_activities ADD CONSTRAINT FK_8811FE1C166D1F9C FOREIGN KEY (project_id) REFERENCES kimai2_projects (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE kimai2_timesheet ADD CONSTRAINT FK_4F60C6B18D93D649 FOREIGN KEY (user) REFERENCES kimai2_users (id)');
            $this->addSql('ALTER TABLE kimai2_timesheet ADD CONSTRAINT FK_4F60C6B181C06096 FOREIGN KEY (activity_id) REFERENCES kimai2_activities (id) ON DELETE CASCADE');
        }
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $platform = $this->getPlatform();

        if (!in_array($platform, ['sqlite', 'mysql'])) {
            $this->abortIf(true, 'Unsupported database platform: ' . $platform);
        }

        if ($platform === 'sqlite') {
            $this->addSql('DROP TABLE kimai2_users');
            $this->addSql('DROP TABLE kimai2_user_preferences');
            $this->addSql('DROP TABLE kimai2_customers');
            $this->addSql('DROP TABLE kimai2_projects');
            $this->addSql('DROP TABLE kimai2_activities');
            $this->addSql('DROP TABLE kimai2_timesheet');
            $this->addSql('DROP TABLE kimai2_invoice_templates');
        } else {
            $this->addSql('ALTER TABLE kimai2_user_preferences DROP FOREIGN KEY FK_8D08F631A76ED395');
            $this->addSql('ALTER TABLE kimai2_timesheet DROP FOREIGN KEY FK_4F60C6B18D93D649');
            $this->addSql('ALTER TABLE kimai2_projects DROP FOREIGN KEY FK_407F12069395C3F3');
            $this->addSql('ALTER TABLE kimai2_activities DROP FOREIGN KEY FK_8811FE1C166D1F9C');
            $this->addSql('ALTER TABLE kimai2_timesheet DROP FOREIGN KEY FK_4F60C6B181C06096');
            $this->addSql('DROP TABLE kimai2_users');
            $this->addSql('DROP TABLE kimai2_user_preferences');
            $this->addSql('DROP TABLE kimai2_customers');
            $this->addSql('DROP TABLE kimai2_projects');
            $this->addSql('DROP TABLE kimai2_activities');
            $this->addSql('DROP TABLE kimai2_timesheet');
            $this->addSql('DROP TABLE kimai2_invoice_templates');
        }
    }
}
