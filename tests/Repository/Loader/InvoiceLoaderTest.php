<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Loader;

use App\Entity\Invoice;
use App\Repository\Loader\InvoiceLoader;

/**
 * @covers \App\Repository\Loader\InvoiceLoader
 * @covers \App\Repository\Loader\InvoiceIdLoader
 */
class InvoiceLoaderTest extends AbstractLoaderTest
{
    public function testLoadResults()
    {
        $em = $this->getEntityManagerMock(2);

        $sut = new InvoiceLoader($em);

        $entity = $this->createMock(Invoice::class);
        $entity->expects($this->once())->method('getId')->willReturn(1);

        $sut->loadResults([$entity]);
    }
}
