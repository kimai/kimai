<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\Customer;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event can be used, to dynamically add meta fields to customers
 */
final class CustomerMetaDefinitionEvent extends Event
{
    /**
     * @var Customer
     */
    private $entity;

    public function __construct(Customer $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity(): Customer
    {
        return $this->entity;
    }
}
