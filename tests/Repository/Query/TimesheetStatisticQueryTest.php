<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\Query\TimesheetStatisticQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimesheetStatisticQuery::class)]
class TimesheetStatisticQueryTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $begin = new \DateTime('2019-05-27');
        $end = new \DateTime('2019-06-27');
        $user = new User();

        $sut = new TimesheetStatisticQuery($begin, $end, [$user]);

        self::assertSame($begin, $sut->getBegin());
        self::assertSame($end, $sut->getEnd());
        self::assertSame([$user], $sut->getUsers());
        self::assertNull($sut->getProject());
        self::assertNull($sut->getCustomer());
    }

    public function testSetterAndGetter(): void
    {
        $sut = new TimesheetStatisticQuery(new \DateTime('2019-05-27'), new \DateTime('2019-06-27'), []);

        $project = new Project();
        $sut->setProject($project);
        self::assertSame($project, $sut->getProject());

        $sut->setProject(null);
        self::assertNull($sut->getProject());

        $customer = new Customer('foo');
        $sut->setCustomer($customer);
        self::assertSame($customer, $sut->getCustomer());

        $sut->setCustomer(null);
        self::assertNull($sut->getCustomer());
    }
}
