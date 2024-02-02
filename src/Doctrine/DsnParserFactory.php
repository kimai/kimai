<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Tools\DsnParser;

final class DsnParserFactory
{
    /**
     * Copied here from Doctrine v3 DriverManager, as they are going to be removed in Doctrine 4.
     *
     * @var array<string, string>
     */
    private static array $driverSchemeAliases = [
        'db2' => 'ibm_db2',
        'mssql' => 'pdo_sqlsrv',
        'mysql' => 'pdo_mysql',
        'mysql2' => 'pdo_mysql', // Amazon RDS, for some weird reason
        'postgres' => 'pdo_pgsql',
        'postgresql' => 'pdo_pgsql',
        'pgsql' => 'pdo_pgsql',
        'sqlite' => 'pdo_sqlite',
        'sqlite3' => 'pdo_sqlite',
    ];

    public function create(): DsnParser
    {
        return new DsnParser(self::$driverSchemeAliases);
    }

    /**
     * @return array<string, array<mixed>|bool|AbstractPlatform|int|string>
     */
    public function parse(
        #[\SensitiveParameter]
        string $dsn
    ): array
    {
        // see https://github.com/doctrine/dbal/pull/5843

        $options = $this->create()->parse($dsn);

        $options = array_merge(
            $options,
            [
                'charset' => 'utf8mb4',
                'defaultTableOptions' => [
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                ]
            ]
        );

        return $options;
    }
}
