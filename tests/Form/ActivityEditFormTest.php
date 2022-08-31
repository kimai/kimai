<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Form\ActivityEditForm;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @covers \App\Form\ActivityEditForm
 */
class ActivityEditFormTest extends TypeTestCase
{
    public function testWithGlobalNewActivity()
    {
        $model = new Activity();
        $form = $this->factory->createBuilder(ActivityEditForm::class, $model);

        $attr = $form->getFormConfig()->getOption('attr');
        self::assertArrayHasKey('data-form-event', $attr);
        self::assertEquals('kimai.activityUpdate', $attr['data-form-event']);

        self::assertTrue($form->has('name'));
        self::assertTrue($form->has('comment'));
        self::assertTrue($form->has('project'));
        self::assertTrue($form->has('color'));
        self::assertTrue($form->has('metaFields'));
        self::assertTrue($form->has('visible'));
        self::assertFalse($form->has('budget'));
        self::assertFalse($form->has('timeBudget'));
        self::assertFalse($form->has('budgetType'));
    }

    public function testWithGlobalNewActivityAndOptionsBudget()
    {
        $model = new Activity();
        $form = $this->factory->createBuilder(ActivityEditForm::class, $model, [
            'include_budget' => true,
        ]);
        self::assertTrue($form->has('budget'));
        self::assertFalse($form->has('timeBudget'));
        self::assertTrue($form->has('budgetType'));
    }

    public function testWithGlobalNewActivityAndOptionsTimeBudget()
    {
        $model = new Activity();
        $form = $this->factory->createBuilder(ActivityEditForm::class, $model, [
            'include_time' => true,
        ]);
        self::assertFalse($form->has('budget'));
        self::assertTrue($form->has('timeBudget'));
        self::assertTrue($form->has('budgetType'));
    }

    public function testWithGlobalNewActivityAndOptionsAllBudget()
    {
        $model = new Activity();
        $form = $this->factory->createBuilder(ActivityEditForm::class, $model, [
            'include_budget' => true,
            'include_time' => true,
        ]);
        self::assertTrue($form->has('budget'));
        self::assertTrue($form->has('timeBudget'));
        self::assertTrue($form->has('budgetType'));
    }

    public function testWithGlobalExistingActivityAndOptions()
    {
        $model = $this->createMock(Activity::class);
        $model->expects($this->once())->method('getId')->willReturn(1);
        $model->expects($this->atLeast(1))->method('isGlobal')->willReturn(true);
        $form = $this->factory->createBuilder(ActivityEditForm::class, $model, [
            'include_budget' => true,
        ]);
        self::assertFalse($form->has('project'));
        self::assertTrue($form->has('budget'));
        self::assertFalse($form->has('timeBudget'));
    }

    public function testWithNonGlobalExistingActivityAndOptions()
    {
        $project = new Project();
        $customer = new Customer('foo');
        $project->setCustomer($customer);
        $model = $this->createMock(Activity::class);

        $model->expects($this->any())->method('getId')->willReturn(1);
        $model->expects($this->any())->method('getProject')->willReturn($project);
        $form = $this->factory->createBuilder(ActivityEditForm::class, $model, [
            'include_budget' => true,
            'include_time' => true,
        ]);
        self::assertTrue($form->has('name'));
        self::assertTrue($form->has('comment'));
        self::assertTrue($form->has('project'));
        self::assertTrue($form->has('color'));
        self::assertTrue($form->has('metaFields'));
        self::assertTrue($form->has('visible'));
        self::assertTrue($form->has('project'));
        self::assertTrue($form->has('budget'));
        self::assertTrue($form->has('timeBudget'));
    }
}
