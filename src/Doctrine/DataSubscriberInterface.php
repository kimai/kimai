<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine;

/**
 * Used to identify EventSubscribers, that work upon EntityManager events and listen on data changes.
 * These Subscribers will deactivated on batch imports, for performance gains and reduced DB queries.
 */
interface DataSubscriberInterface
{
}
