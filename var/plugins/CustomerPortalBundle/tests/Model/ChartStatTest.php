<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\CustomerPortalBundle\tests\Model;

use KimaiPlugin\CustomerPortalBundle\Model\ChartStat;
use PHPUnit\Framework\TestCase;

class ChartStatTest extends TestCase
{
    public function testDefault(): void
    {
        $chartStat = new ChartStat();
        self::assertEquals(0, $chartStat->getDuration());
        self::assertEquals(0, $chartStat->getRate());
    }

    public function testValidRow(): void
    {
        $chartStat = new ChartStat([
            'duration' => 1,
            'rate' => 2.2,
        ]);
        self::assertEquals(1, $chartStat->getDuration());
        self::assertEquals(2.2, $chartStat->getRate());
    }
}
