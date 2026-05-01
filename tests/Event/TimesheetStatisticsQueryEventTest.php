<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Event\TimesheetStatisticsQueryEvent;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimesheetStatisticsQueryEvent::class)]
class TimesheetStatisticsQueryEventTest extends TestCase
{
    public function testGetter(): void
    {
        $qb = new QueryBuilder($this->createMock(EntityManager::class));
        self::assertCount(0, $qb->getParameters());
        $sut = new TimesheetStatisticsQueryEvent($qb);
        $qb->setParameter('foo', 'bar');

        self::assertSame($qb, $sut->getQueryBuilder());
        self::assertCount(1, $sut->getQueryBuilder()->getParameters());
    }
}
