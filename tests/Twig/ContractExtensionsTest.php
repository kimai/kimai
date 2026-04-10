<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Twig\ContractExtensions;
use App\WorkingTime\WorkingTimeService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\TwigTest;

#[CoversClass(ContractExtensions::class)]
class ContractExtensionsTest extends TestCase
{
    private function getSut(): ContractExtensions
    {
        $service = $this->createMock(WorkingTimeService::class);

        $sut = new ContractExtensions($service);

        return $sut;
    }

    public function testDefinedMethods(): void
    {
        self::assertCount(1, $this->getSut()->getTests());
        self::assertCount(0, $this->getSut()->getFilters());
        self::assertCount(0, $this->getSut()->getFunctions());
    }

    public function testGetTests(): void
    {
        $filters = [
            'work_day',
        ];
        $i = 0;

        $sut = $this->getSut();
        $twigFilters = $sut->getTests();

        foreach ($twigFilters as $filter) {
            self::assertInstanceOf(TwigTest::class, $filter);
            self::assertEquals($filters[$i++], $filter->getName());
        }
    }
}
