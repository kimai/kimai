<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\Customer;

class ProjectQuery extends BaseQuery implements VisibilityInterface
{
    use VisibilityTrait;

    public const PROJECT_ORDER_ALLOWED = [
        'name', 'description' => 'comment', 'customer', 'orderNumber', 'orderDate',
        'project_start', 'project_end', 'budget', 'timeBudget', 'visible'
    ];

    /**
     * @var array<Customer>
     */
    private array $customers = [];
    private ?\DateTime $projectStart = null;
    private ?\DateTime $projectEnd = null;
    private ?bool $globalActivities = null;

    public function __construct()
    {
        $this->setDefaults([
            'orderBy' => 'name',
            'customers' => [],
            'projectStart' => null,
            'projectEnd' => null,
            'visibility' => VisibilityInterface::SHOW_VISIBLE,
            'globalActivities' => null,
        ]);
    }

    public function addCustomer(Customer $customer): self
    {
        $this->customers[] = $customer;

        return $this;
    }

    /**
     * @param array<Customer> $customers
     * @return $this
     */
    public function setCustomers(array $customers): self
    {
        $this->customers = $customers;

        return $this;
    }

    /**
     * @return array<Customer>
     */
    public function getCustomers(): array
    {
        return $this->customers;
    }

    /**
     * @return array<int>
     */
    public function getCustomerIds(): array
    {
        return array_filter(array_values(array_unique(array_map(function (Customer $customer) {
            return $customer->getId();
        }, $this->customers))), function ($id) {
            return $id !== null;
        });
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

    public function getGlobalActivities(): ?bool
    {
        return $this->globalActivities;
    }

    public function setGlobalActivities(?bool $globalActivities): void
    {
        $this->globalActivities = $globalActivities;
    }
}
