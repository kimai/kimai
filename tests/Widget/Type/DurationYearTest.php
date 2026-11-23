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
use App\Widget\Type\AbstractWidget;
use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\DurationYear;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DurationYear::class)]
#[CoversClass(AbstractCounterYear::class)]
class DurationYearTest extends AbstractWidgetTypeTestCase
{
    /**
     * @return AbstractCounterYear
     */
    public function createSut(): AbstractWidgetType
    {
        $repository = $this->createMock(TimesheetRepository::class);

        $sut = new DurationYear($repository);
        $sut->setUser(new User());

        return $sut;
    }

    protected function assertDefaultData(AbstractWidget $sut): void
    {
        self::assertEquals(0, $sut->getData());
    }

    public function getDefaultOptions(): array
    {
        return [
            'icon' => 'duration',
            'color' => 'yellow',
        ];
    }

    public function testSettings(): void
    {
        $sut = $this->createSut();

        self::assertEquals('widget/widget-counter-duration.html.twig', $sut->getTemplateName());
        self::assertEquals('DurationYear', $sut->getId());
    }

    public function testUsesTheCalendarYear(): void
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $repository->expects($this->once())->method('getDurationForTimeRange')->willReturnCallback(
            function (\DateTimeInterface $begin, \DateTimeInterface $end) {
                self::assertEquals(date('Y') . '-01-01 00:00:00', $begin->format('Y-m-d H:i:s'));
                self::assertEquals(date('Y') . '-12-31 23:59:59', $end->format('Y-m-d H:i:s'));

                return 1234;
            }
        );

        $sut = new DurationYear($repository);
        $sut->setUser(new User());

        self::assertEquals(1234, $sut->getData());
        self::assertEquals('stats.durationYear', $sut->getTitle());
    }
}
