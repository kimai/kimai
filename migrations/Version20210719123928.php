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
final class Version20210719123928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the budget_type columns to customer, project and activity and fixes row format issues';
    }

    public function up(Schema $schema): void
    {
        // Fix row format to prevent "Row size too large" errors
        $this->addSql('ALTER TABLE kimai2_activities ROW_FORMAT=DYNAMIC');
        $this->addSql('ALTER TABLE kimai2_customers ROW_FORMAT=DYNAMIC');
        $this->addSql('ALTER TABLE kimai2_projects ROW_FORMAT=DYNAMIC');

        // Optimize column sizes to reduce row size
        $this->addSql('ALTER TABLE kimai2_customers MODIFY COLUMN name VARCHAR(64)');
        $this->addSql('ALTER TABLE kimai2_customers MODIFY COLUMN number VARCHAR(64)');
        $this->addSql('ALTER TABLE kimai2_customers MODIFY COLUMN company VARCHAR(64)');
        $this->addSql('ALTER TABLE kimai2_customers MODIFY COLUMN contact VARCHAR(64)');
        $this->addSql('ALTER TABLE kimai2_customers MODIFY COLUMN country VARCHAR(64)');
        $this->addSql('ALTER TABLE kimai2_customers MODIFY COLUMN currency VARCHAR(64)');
        $this->addSql('ALTER TABLE kimai2_customers MODIFY COLUMN phone VARCHAR(64)');
        $this->addSql('ALTER TABLE kimai2_customers MODIFY COLUMN fax VARCHAR(64)');
        $this->addSql('ALTER TABLE kimai2_customers MODIFY COLUMN mobile VARCHAR(64)');
        $this->addSql('ALTER TABLE kimai2_customers MODIFY COLUMN email VARCHAR(64)');
        $this->addSql('ALTER TABLE kimai2_customers MODIFY COLUMN homepage VARCHAR(64)');
        $this->addSql('ALTER TABLE kimai2_customers MODIFY COLUMN timezone VARCHAR(64)');
        $this->addSql('ALTER TABLE kimai2_customers MODIFY COLUMN color VARCHAR(64)');
        $this->addSql('ALTER TABLE kimai2_customers MODIFY COLUMN vat_id VARCHAR(64)');

        $this->addSql('ALTER TABLE kimai2_projects MODIFY COLUMN name VARCHAR(64)');
        $this->addSql('ALTER TABLE kimai2_projects MODIFY COLUMN color VARCHAR(64)');
        $this->addSql('ALTER TABLE kimai2_projects MODIFY COLUMN timezone VARCHAR(64)');

        $this->addSql('ALTER TABLE kimai2_activities MODIFY COLUMN name VARCHAR(64)');
        $this->addSql('ALTER TABLE kimai2_activities MODIFY COLUMN color VARCHAR(64)');

        // Add the original budget_type columns (using the original data type)
        $activities = $schema->getTable('kimai2_activities');
        $activities->addColumn('budget_type', 'string', ['length' => 10, 'notnull' => false, 'default' => null]);

        $customers = $schema->getTable('kimai2_customers');
        $customers->addColumn('budget_type', 'string', ['length' => 10, 'notnull' => false, 'default' => null]);

        $projects = $schema->getTable('kimai2_projects');
        $projects->addColumn('budget_type', 'string', ['length' => 10, 'notnull' => false, 'default' => null]);
    }

    public function down(Schema $schema): void
    {
        $activities = $schema->getTable('kimai2_activities');
        $activities->dropColumn('budget_type');

        $customers = $schema->getTable('kimai2_customers');
        $customers->dropColumn('budget_type');

        $projects = $schema->getTable('kimai2_projects');
        $projects->dropColumn('budget_type');

        // Note: We intentionally don't revert the VARCHAR column sizes and ROW_FORMAT changes
        // as they're necessary for database stability. Reverting these could cause data loss.
    }
}
