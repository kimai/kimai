<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Entity\User;
use App\Repository\TimesheetRepository;
use App\Widget\Type\AbstractCounterYear;
use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\AmountYear;
use App\Widget\WidgetInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[CoversClass(AmountYear::class)]
#[CoversClass(AbstractCounterYear::class)]
class AmountYearTest extends AbstractWidgetTypeTestCase
{
    protected function assertDefaultData(AbstractWidgetType $sut): void
    {
        self::assertEquals([], $sut->getData());
    }

    /**
     * @return AbstractCounterYear
     */
    public function createSut(): AbstractWidgetType
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $sut = new AmountYear($repository, $dispatcher);
        $sut->setUser(new User());

        return $sut;
    }

    public function getDefaultOptions(): array
    {
        return [
            'icon' => 'money',
            'color' => WidgetInterface::COLOR_YEAR,
        ];
    }

    public function testSettings(): void
    {
        $sut = $this->createSut();

        self::assertEquals('widget/widget-counter-money.html.twig', $sut->getTemplateName());
        self::assertEquals('AmountYear', $sut->getId());
    }

    public function testUsesTheCalendarYear(): void
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $repository->expects($this->once())->method('getRevenue')->willReturnCallback(
            function (\DateTimeInterface $begin, \DateTimeInterface $end) {
                self::assertEquals(date('Y') . '-01-01 00:00:00', $begin->format('Y-m-d H:i:s'));
                self::assertEquals(date('Y') . '-12-31 23:59:59', $end->format('Y-m-d H:i:s'));

                return [];
            }
        );

        $sut = new AmountYear($repository, $this->createMock(EventDispatcherInterface::class));
        $sut->setUser(new User());

        self::assertEquals([], $sut->getData());
        self::assertEquals('stats.amountYear', $sut->getTitle());
    }
}
