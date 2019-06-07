<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\Customer;

/**
 * Can be used for advanced queries with the: ProjectRepository
 */
class ProjectQuery extends VisibilityQuery
{
    /**
     * @var Customer|int|null
     */
    protected $customer;

    /**
     * @var array
     */
    protected $ignored = [];

    /**
     * @param mixed $entity
     * @return $this
     */
    public function addIgnoredEntity($entity)
    {
        $this->ignored[] = $entity;

        return $this;
    }

    /**
     * @return array
     */
    public function getIgnoredEntities()
    {
        return $this->ignored;
    }

    /**
     * @return Customer|int|null
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer|int|null $customer
     * @return $this
     */
    public function setCustomer($customer = null)
    {
        $this->customer = $customer;

        return $this;
    }
}
