<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Entity\Customer;
use App\Entity\EntityWithBudget;
use App\Model\BudgetStatisticModel;
use App\Model\CustomerBudgetStatisticModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\CustomerBudgetStatisticModel
 */
class CustomerBudgetStatisticModelTest extends TestCase
{
    /**
     * @param EntityWithBudget $entity
     * @return CustomerBudgetStatisticModel
     */
    protected function getSut(EntityWithBudget $entity): BudgetStatisticModel
    {
        \assert($entity instanceof Customer);

        return new CustomerBudgetStatisticModel($entity);
    }

    protected function getEntity(): EntityWithBudget
    {
        return new Customer('foo');
    }

    public function testAdditionals(): void
    {
        $entity = $this->getEntity();
        $sut = $this->getSut($entity);

        self::assertInstanceOf(Customer::class, $sut->getEntity());
        self::assertSame($entity, $sut->getEntity());
        self::assertSame($entity, $sut->getCustomer());
    }
}
