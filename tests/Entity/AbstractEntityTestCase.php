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

abstract class AbstractEntityTestCase extends TestCase
{
    public function assertBudget(EntityWithBudget $entityWithBudget): void
    {
        self::assertEquals(0.0, $entityWithBudget->getBudget());
        self::assertEquals(0, $entityWithBudget->getTimeBudget());
        self::assertNull($entityWithBudget->getBudgetType());
        self::assertFalse($entityWithBudget->isMonthlyBudget());

        self::assertFalse($entityWithBudget->hasBudget());
        self::assertFalse($entityWithBudget->hasTimeBudget());

        $entityWithBudget->setBudget(12345.67);
        self::assertEquals(12345.67, $entityWithBudget->getBudget());
        self::assertTrue($entityWithBudget->hasBudget());
        self::assertFalse($entityWithBudget->hasTimeBudget());

        $entityWithBudget->setTimeBudget(937321);
        self::assertEquals(937321, $entityWithBudget->getTimeBudget());
        self::assertTrue($entityWithBudget->hasTimeBudget());

        $entityWithBudget->setBudgetType('month');
        self::assertTrue($entityWithBudget->isMonthlyBudget());
        $entityWithBudget->setBudgetType(null);
        self::assertFalse($entityWithBudget->isMonthlyBudget());

        try {
            $entityWithBudget->setBudgetType('foo');
            $this->fail('Budget type only allows "month"');
        } catch (\InvalidArgumentException $e) {
            self::assertEquals('Unknown budget type: foo', $e->getMessage());
        }
    }
}
