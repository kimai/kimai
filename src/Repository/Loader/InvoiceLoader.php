<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Invoice;
use Doctrine\ORM\EntityManagerInterface;

final class InvoiceLoader implements LoaderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param Invoice[] $results
     */
    public function loadResults(array $results): void
    {
        $ids = array_map(function (Invoice $invoice) {
            return $invoice->getId();
        }, $results);

        $loader = new InvoiceIdLoader($this->entityManager);
        $loader->loadResults($ids);
    }
}
