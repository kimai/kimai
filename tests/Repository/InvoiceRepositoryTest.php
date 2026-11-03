<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Entity\Invoice;
use App\Repository\InvoiceRepository;
use App\Repository\Query\InvoiceArchiveQuery;
use App\Tests\DataFixtures\InvoiceFixtures;
use App\Utils\SearchTerm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(InvoiceRepository::class)]
#[Group('integration')]
class InvoiceRepositoryTest extends AbstractRepositoryTestCase
{
    public function testGetInvoicesForQueryFindsInvoiceByNumber(): void
    {
        $fixture = new InvoiceFixtures();
        $fixture->setAmount(2);
        [$invoice, $other] = $this->importFixture($fixture);

        /** @var InvoiceRepository $repository */
        $repository = $this->getEntityManager()->getRepository(Invoice::class);

        $invoice->setInvoiceNumber('2026/008');
        $repository->saveInvoice($invoice);
        $other->setInvoiceNumber('2026/009');
        $repository->saveInvoice($other);

        $query = new InvoiceArchiveQuery();
        $query->setSearchTerm(new SearchTerm('2026/008'));

        $result = [...$repository->getInvoicesForQuery($query)];
        self::assertCount(1, $result);
        self::assertSame($invoice->getId(), $result[0]->getId());
    }
}
