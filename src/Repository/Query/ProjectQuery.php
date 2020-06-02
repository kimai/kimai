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
     * @var array
     */
    private $customers = [];
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
     * @deprecated since 1.9 - use getCustomers() instead - will be removed with 2.0
     */
    public function getCustomer()
    {
        if (\count($this->customers) > 0) {
            return $this->customers[0];
        }

        return null;
    }

    /**
     * @param Customer|int|null $customer
     * @return $this
     * @deprecated since 1.9 - use setCustomers() or addCustomer() instead - will be removed with 2.0
     */
    public function setCustomer($customer = null)
    {
        if (null === $customer) {
            $this->customers = [];
        } else {
            $this->customers = [$customer];
        }

        return $this;
    }

    /**
     * @param Customer|int $customer
     * @return $this
     */
    public function addCustomer($customer)
    {
        $this->customers[] = $customer;

        return $this;
    }

    public function setCustomers(array $customers): self
    {
        $this->customers = $customers;

        return $this;
    }

    public function getCustomers(): array
    {
        return $this->customers;
    }

    public function hasCustomers(): bool
    {
        return !empty($this->customers);
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
