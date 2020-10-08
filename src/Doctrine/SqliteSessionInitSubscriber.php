<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;

class SqliteSessionInitSubscriber implements EventSubscriber
{
    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postConnect,
        ];
    }

    /**
     * @param ConnectionEventArgs $args
     * @throws \Doctrine\DBAL\DBALException
     */
    public function postConnect(ConnectionEventArgs $args)
    {
        if ('sqlite' !== strtolower($args->getConnection()->getDatabasePlatform()->getName())) {
            return;
        }

        $args->getConnection()->exec('PRAGMA foreign_keys = ON;');
    }
}
