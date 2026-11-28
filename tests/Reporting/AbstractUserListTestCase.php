<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting;

use App\Entity\Customer;
use App\Entity\Project;
use App\Reporting\AbstractUserList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractUserList::class)]
abstract class AbstractUserListTestCase extends TestCase
{
    abstract protected function createSut(): AbstractUserList;

    public function testEmptyObject(): void
    {
        $sut = $this->createSut();
        self::assertNull($sut->getDate());
        self::assertEquals('duration', $sut->getSumType());
        self::assertFalse($sut->isDecimal());
        self::assertNull($sut->getTeam());
        self::assertNull($sut->getProject());
        self::assertNull($sut->getCustomer());
    }

    public function testSetter(): void
    {
        $date = new \DateTime('2019-05-27');

        $sut = $this->createSut();
        $sut->setDate($date);

        self::assertSame($date, $sut->getDate());

        $sut->setSumType('rate');
        self::assertEquals('rate', $sut->getSumType());

        $sut->setSumType('internalRate');
        self::assertEquals('internalRate', $sut->getSumType());

        $sut->setSumType('duration');
        self::assertEquals('duration', $sut->getSumType());

        $sut->setDecimal(true);
        self::assertTrue($sut->isDecimal());

        $sut->setDecimal(false);
        self::assertFalse($sut->isDecimal());

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

        $sut->setCustomer($customer);
        $sut->setCustomer();
        self::assertNull($sut->getCustomer());
    }

    public function testInvalidSumType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $sut = $this->createSut();
        $sut->setSumType('DURation');
    }
}
