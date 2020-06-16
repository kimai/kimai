<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use Doctrine\DBAL\Driver\PDOConnection;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

class SessionHandler extends PdoSessionHandler
{
    public function __construct($pdoOrDsn = null)
    {
        $lockMode = PdoSessionHandler::LOCK_NONE;

        if ($pdoOrDsn instanceof PDOConnection) {
            if ($pdoOrDsn->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
                $lockMode = PdoSessionHandler::LOCK_ADVISORY;
            }
        }

        parent::__construct($pdoOrDsn, [
            'db_table' => 'kimai2_sessions',
            'db_id_col' => 'id',
            'db_data_col' => 'data',
            'db_lifetime_col' => 'lifetime',
            'db_time_col' => 'time',
            'lock_mode' => $lockMode,
        ]);
    }
}
