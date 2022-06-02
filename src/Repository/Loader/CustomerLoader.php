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
    public function __construct(private EntityManagerInterface $entityManager, private bool $fullyHydrated = false)
    {
    }

    /**
     * @param Customer[] $results
     */
    public function loadResults(array $results): void
    {
        $ids = array_map(function (Customer $customer) {
            return $customer->getId();
        }, $results);

        $loader = new CustomerIdLoader($this->entityManager, $this->fullyHydrated);
        $loader->loadResults($ids);
    }
}
