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
    /**
     * @var CustomerIdLoader
     */
    private $loader;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->loader = new CustomerIdLoader($entityManager);
    }

    /**
     * @param Customer[] $customers
     */
    public function loadResults(array $customers): void
    {
        $ids = array_map(function (Customer $customer) {
            return $customer->getId();
        }, $customers);

        $this->loader->loadResults($ids);
    }
}
