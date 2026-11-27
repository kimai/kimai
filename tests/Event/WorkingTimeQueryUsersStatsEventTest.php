<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\WorkingTimeQueryUsersStatsEvent;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WorkingTimeQueryUsersStatsEvent::class)]
class WorkingTimeQueryUsersStatsEventTest extends TestCase
{
    public function testGetter(): void
    {
        $qb = new QueryBuilder($this->createMock(EntityManager::class));
        self::assertCount(0, $qb->getParameters());

        $users = [new User(), new User()];
        $begin = new \DateTime('2004-02-13');
        $end = new \DateTime('2099-12-31');

        $sut = new WorkingTimeQueryUsersStatsEvent($qb, $users, $begin, $end);
        $qb->setParameter('foo', 'bar');

        self::assertSame($qb, $sut->getQueryBuilder());
        self::assertCount(1, $sut->getQueryBuilder()->getParameters());
        self::assertSame($users, $sut->getUsers());
        self::assertSame($begin, $sut->getBegin());
        self::assertSame($end, $sut->getEnd());
    }
}
