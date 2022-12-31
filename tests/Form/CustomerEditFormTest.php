<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form;

use App\Entity\Customer;
use App\Form\CustomerEditForm;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @covers \App\Form\CustomerEditForm
 */
class CustomerEditFormTest extends TypeTestCase
{
    public function testWithNewProject()
    {
        $model = new Customer('foo');
        $form = $this->factory->createBuilder(CustomerEditForm::class, $model);

        $attr = $form->getFormConfig()->getOption('attr');
        self::assertArrayHasKey('data-form-event', $attr);
        self::assertEquals('kimai.customerUpdate', $attr['data-form-event']);

        self::assertTrue($form->has('name'));
        self::assertTrue($form->has('comment'));
        self::assertTrue($form->has('color'));
        self::assertTrue($form->has('metaFields'));
        self::assertTrue($form->has('visible'));
        self::assertFalse($form->has('budget'));
        self::assertFalse($form->has('timeBudget'));
        self::assertFalse($form->has('budgetType'));
    }

    public function testWithBudget()
    {
        $model = new Customer('foo');
        $form = $this->factory->createBuilder(CustomerEditForm::class, $model, [
            'include_budget' => true,
        ]);
        self::assertTrue($form->has('budget'));
        self::assertFalse($form->has('timeBudget'));
        self::assertTrue($form->has('budgetType'));
    }

    public function testWithTimeBudget()
    {
        $model = new Customer('foo');
        $form = $this->factory->createBuilder(CustomerEditForm::class, $model, [
            'include_time' => true,
        ]);
        self::assertFalse($form->has('budget'));
        self::assertTrue($form->has('timeBudget'));
        self::assertTrue($form->has('budgetType'));
    }

    public function testWithBudgetAndTimeBudget()
    {
        $model = new Customer('foo');
        $form = $this->factory->createBuilder(CustomerEditForm::class, $model, [
            'include_budget' => true,
            'include_time' => true,
        ]);
        self::assertTrue($form->has('budget'));
        self::assertTrue($form->has('timeBudget'));
        self::assertTrue($form->has('budgetType'));
    }
}
