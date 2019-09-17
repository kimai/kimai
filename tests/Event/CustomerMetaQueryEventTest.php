<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\CustomerMeta;
use App\Event\CustomerMetaQueryEvent;
use App\Repository\Query\CustomerQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\CustomerMetaQueryEvent
 */
class CustomerMetaQueryEventTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $query = new CustomerQuery();
        $sut = new CustomerMetaQueryEvent($query);

        self::assertSame($sut->getQuery(), $query);
        self::assertIsArray($sut->getFields());
        self::assertEmpty($sut->getFields());

        $sut->addField(new CustomerMeta());
        $sut->addField(new CustomerMeta());

        self::assertCount(2, $sut->getFields());
    }
}
