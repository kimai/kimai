<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

class CustomerQuery extends BaseQuery implements VisibilityInterface
{
    use VisibilityTrait;

    public const CUSTOMER_ORDER_ALLOWED = [
        'name',
        'description' => 'comment',
        'country', 'number',
        'homepage',
        'email',
        'mobile',
        'fax',
        'phone',
        'currency',
        'address',
        'contact',
        'company',
        'vat_id',
        'budget',
        'timeBudget',
        'visible'
    ];

    private ?string $country = null;
    /**
     * @var array<int>
     */
    private array $customerIds = [];
    /**
     * @var array<CustomerQueryHydrate>
     */
    private array $hydrate = [];

    public function __construct()
    {
        $this->setDefaults([
            'orderBy' => 'name',
            'visibility' => VisibilityInterface::SHOW_VISIBLE,
            'country' => null,
            'customerIds' => [],
        ]);
    }

    protected function copyFrom(BaseQuery $query): void
    {
        parent::copyFrom($query);

        if ($query instanceof CustomerQuery) {
            $this->setCustomerIds($query->getCustomerIds());
            $this->setCountry($query->getCountry());
            foreach ($query->getHydrate() as $hydrate) {
                $this->addHydrate($hydrate);
            }
        }
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    /**
     * @param array<int> $ids
     */
    public function setCustomerIds(array $ids): void
    {
        $this->customerIds = $ids;
    }

    /**
     * @return int[]
     */
    public function getCustomerIds(): array
    {
        return $this->customerIds;
    }

    private function addHydrate(CustomerQueryHydrate $hydrate): void
    {
        if (!\in_array($hydrate, $this->hydrate, true)) {
            $this->hydrate[] = $hydrate;
        }
    }

    /**
     * @return CustomerQueryHydrate[]
     */
    public function getHydrate(): array
    {
        return $this->hydrate;
    }

    public function loadTeams(): void
    {
        $this->addHydrate(CustomerQueryHydrate::TEAMS);
    }
}
