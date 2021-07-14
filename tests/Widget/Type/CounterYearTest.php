<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Configuration\SystemConfiguration;
use App\Entity\User;
use App\Repository\TimesheetRepository;
use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\Counter;
use App\Widget\Type\CounterYear;
use App\Widget\Type\SimpleWidget;
use DateTime;

/**
 * @covers \App\Widget\Type\CounterYear
 * @covers \App\Widget\Type\SimpleStatisticChart
 * @covers \App\Widget\Type\SimpleWidget
 */
class CounterYearTest extends AbstractSimpleStatisticsWidgetTypeTest
{
    public function createSut(?string $financialYear = null): AbstractWidgetType
    {
        $configuration = $this->createMock(SystemConfiguration::class);

        if (null !== $financialYear) {
            $configuration->method('getFinancialYearStart')->willReturn($financialYear);
        }

        $sut = new CounterYear($this->createMock(TimesheetRepository::class), $configuration);
        $sut->setQuery(TimesheetRepository::STATS_QUERY_ACTIVE);

        return $sut;
    }

    public function testQueryWithUser()
    {
        $user = new User();
        $user->setAlias('foo');

        $repository = $this->createMock(TimesheetRepository::class);
        $repository->expects($this->once())->method('getStatistic')->willReturnCallback(function (string $type, ?DateTime $begin, ?DateTime $end, ?User $user) {
            self::assertEquals($type, 'active');
            self::assertNull($begin);
            self::assertNull($end);
            self::assertNull($user);
        });
        $sut = new Counter($repository);
        $sut->setQuery(TimesheetRepository::STATS_QUERY_ACTIVE);
        $sut->setUser($user);
        $sut->getData([]);

        $user = new User();
        $user->setAlias('bar');

        $repository = $this->createMock(TimesheetRepository::class);
        $repository->expects($this->once())->method('getStatistic')->willReturnCallback(function (string $type, ?DateTime $begin, ?DateTime $end, ?User $user) {
            self::assertEquals($type, 'active');
            self::assertNull($begin);
            self::assertNull($end);
            self::assertNotNull($user);
            self::assertEquals('bar', $user->getAlias());
        });
        $sut = new Counter($repository);
        $sut->setQuery(TimesheetRepository::STATS_QUERY_ACTIVE);
        $sut->setUser($user);
        $sut->setQueryWithUser(true);
        $sut->getData([]);
    }

    public function getDefaultOptions(): array
    {
        return ['dataType' => 'int'];
    }

    public function testExtendsSimpleWidget()
    {
        $sut = $this->createSut();
        self::assertInstanceOf(SimpleWidget::class, $sut);
    }

    public function testTemplateName()
    {
        /** @var Counter $sut */
        $sut = $this->createSut();
        self::assertEquals('widget/widget-counter.html.twig', $sut->getTemplateName());
    }

    public function testTemplateNameWithFinancialYear()
    {
        /** @var Counter $sut */
        $sut = $this->createSut('2020-01-01');
        self::assertEquals('widget/widget-counter.html.twig', $sut->getTemplateName());
    }
}
