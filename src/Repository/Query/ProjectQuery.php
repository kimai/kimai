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
class ProjectQuery extends BaseQuery implements VisibilityInterface
{
    use VisibilityTrait;

    public const PROJECT_ORDER_ALLOWED = ['id', 'name', 'comment', 'customer', 'orderNumber', 'projectStart', 'projectEnd'];

    /**
     * @var Customer|int|null
     */
    private $customer;
    /**
     * @var \DateTime
     */
    private $projectStart;
    /**
     * @var \DateTime
     */
    private $projectEnd;

    public function __construct()
    {
        $this->setDefaults([
            'orderBy' => 'name',
        ]);
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

    public function getProjectStart(): ?\DateTime
    {
        return $this->projectStart;
    }

    public function setProjectStart(?\DateTime $projectStart): ProjectQuery
    {
        $this->projectStart = $projectStart;

        return $this;
    }

    public function getProjectEnd(): ?\DateTime
    {
        return $this->projectEnd;
    }

    public function setProjectEnd(?\DateTime $projectEnd): ProjectQuery
    {
        $this->projectEnd = $projectEnd;

        return $this;
    }
}
