<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\CustomerMeta;
use App\Event\AbstractMetaDisplayEvent;
use App\Event\CustomerMetaDisplayEvent;
use App\Event\MetaDisplayEventInterface;
use App\Repository\Query\CustomerQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractMetaDisplayEvent::class)]
#[CoversClass(CustomerMetaDisplayEvent::class)]
class CustomerMetaDisplayEventTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $query = new CustomerQuery();
        $sut = new CustomerMetaDisplayEvent($query, CustomerMetaDisplayEvent::EXPORT);

        self::assertInstanceOf(MetaDisplayEventInterface::class, $sut);
        self::assertSame($sut->getQuery(), $query);
        self::assertIsArray($sut->getFields());
        self::assertEmpty($sut->getFields());
        self::assertEquals('export', $sut->getLocation());

        $sut->addField(new CustomerMeta());
        $sut->addField(new CustomerMeta());

        self::assertCount(2, $sut->getFields());
    }
}
