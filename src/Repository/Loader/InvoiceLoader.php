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
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Invoice[] $invoices
     */
    public function loadResults(array $invoices): void
    {
        $ids = array_map(function (Invoice $invoice) {
            return $invoice->getId();
        }, $invoices);

        $loader = new InvoiceIdLoader($this->entityManager);
        $loader->loadResults($ids);
    }
}
