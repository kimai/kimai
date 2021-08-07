<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;

final class CustomerLoader implements LoaderInterface
{
    private $entityManager;
    private $fullyHydrated;

    public function __construct(EntityManagerInterface $entityManager, bool $fullyHydrated = false)
    {
        $this->entityManager = $entityManager;
        $this->fullyHydrated = $fullyHydrated;
    }

    /**
     * @param Customer[] $customers
     */
    public function loadResults(array $customers): void
    {
        $ids = array_map(function (Customer $customer) {
            return $customer->getId();
        }, $customers);

        $loader = new CustomerIdLoader($this->entityManager, $this->fullyHydrated);
        $loader->loadResults($ids);
    }
}
