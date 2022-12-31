<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

final class SessionHandler extends PdoSessionHandler
{
    public function __construct(Connection $connection)
    {
        parent::__construct($connection->getNativeConnection(), [
            'db_table' => 'kimai2_sessions',
            'db_id_col' => 'id',
            'db_data_col' => 'data',
            'db_lifetime_col' => 'lifetime',
            'db_time_col' => 'time',
            'lock_mode' => PdoSessionHandler::LOCK_ADVISORY,
        ]);
    }
}
