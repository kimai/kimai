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
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;

/**
 * @version 1.19.2
 */
final class Version20220404150236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow arbitrary string length for system configurations';
    }

    public function up(Schema $schema): void
    {
        $configuration = $schema->getTable('kimai2_configuration');
        $configuration->getColumn('value')->setType(Type::getType(Types::TEXT));
    }

    public function down(Schema $schema): void
    {
        $configuration = $schema->getTable('kimai2_configuration');
        $configuration->getColumn('value')->setLength(1024)->setType(Type::getType(Types::STRING));
    }
}
