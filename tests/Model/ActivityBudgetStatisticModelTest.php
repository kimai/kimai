<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Entity\Activity;
use App\Entity\EntityWithBudget;
use App\Model\ActivityBudgetStatisticModel;
use App\Model\BudgetStatisticModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\ActivityBudgetStatisticModel
 */
class ActivityBudgetStatisticModelTest extends TestCase
{
    /**
     * @param EntityWithBudget $entity
     * @return ActivityBudgetStatisticModel
     */
    protected function getSut(EntityWithBudget $entity): BudgetStatisticModel
    {
        \assert($entity instanceof Activity);

        return new ActivityBudgetStatisticModel($entity);
    }

    protected function getEntity(): EntityWithBudget
    {
        return new Activity();
    }

    public function testAdditionals(): void
    {
        $entity = $this->getEntity();
        $sut = $this->getSut($entity);

        self::assertInstanceOf(Activity::class, $sut->getEntity());
        self::assertSame($entity, $sut->getEntity());
        self::assertSame($entity, $sut->getActivity());
    }
}
