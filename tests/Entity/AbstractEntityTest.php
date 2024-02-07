<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\EntityWithBudget;
use PHPUnit\Framework\TestCase;

abstract class AbstractEntityTest extends TestCase
{
    public function assertBudget(EntityWithBudget $entityWithBudget): void
    {
        $this->assertEquals(0.0, $entityWithBudget->getBudget());
        $this->assertEquals(0, $entityWithBudget->getTimeBudget());
        $this->assertNull($entityWithBudget->getBudgetType());
        $this->assertFalse($entityWithBudget->isMonthlyBudget());

        self::assertFalse($entityWithBudget->hasBudget());
        self::assertFalse($entityWithBudget->hasTimeBudget());

        $entityWithBudget->setBudget(12345.67);
        $this->assertEquals(12345.67, $entityWithBudget->getBudget());
        self::assertTrue($entityWithBudget->hasBudget());
        self::assertFalse($entityWithBudget->hasTimeBudget());

        $entityWithBudget->setTimeBudget(937321);
        $this->assertEquals(937321, $entityWithBudget->getTimeBudget());
        self::assertTrue($entityWithBudget->hasTimeBudget());

        $entityWithBudget->setBudgetType('month');
        $this->assertTrue($entityWithBudget->isMonthlyBudget());
        $entityWithBudget->setBudgetType(null);
        $this->assertFalse($entityWithBudget->isMonthlyBudget());

        try {
            $entityWithBudget->setBudgetType('foo');
            $this->fail('Budget type only allows "month"');
        } catch (\InvalidArgumentException $e) {
            self::assertEquals('Unknown budget type: foo', $e->getMessage());
        }
    }
}
