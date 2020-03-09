<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Invoice;
use Doctrine\ORM\EntityRepository;

class InvoiceRepository extends EntityRepository
{
    public function saveInvoice(Invoice $invoice)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($invoice);
        $entityManager->flush();
    }
}
