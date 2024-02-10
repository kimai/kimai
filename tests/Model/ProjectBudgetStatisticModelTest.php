<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Entity\EntityWithBudget;
use App\Entity\Project;
use App\Model\BudgetStatisticModel;
use App\Model\ProjectBudgetStatisticModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\ProjectBudgetStatisticModel
 */
class ProjectBudgetStatisticModelTest extends TestCase
{
    /**
     * @param EntityWithBudget $entity
     * @return ProjectBudgetStatisticModel
     */
    protected function getSut(EntityWithBudget $entity): BudgetStatisticModel
    {
        \assert($entity instanceof Project);

        return new ProjectBudgetStatisticModel($entity);
    }

    protected function getEntity(): EntityWithBudget
    {
        return new Project();
    }

    public function testAdditionals(): void
    {
        $entity = $this->getEntity();
        $sut = $this->getSut($entity);

        self::assertInstanceOf(Project::class, $sut->getEntity());
        self::assertSame($entity, $sut->getEntity());
        self::assertSame($entity, $sut->getProject());
    }
}
