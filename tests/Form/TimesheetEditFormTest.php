<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form;

use App\Configuration\SystemConfiguration;
use App\Form\TimesheetEditForm;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(TimesheetEditForm::class)]
class TimesheetEditFormTest extends TypeTestCase
{
    private SystemConfiguration $systemConfiguration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->systemConfiguration = $this->createMock(SystemConfiguration::class);

        $this->systemConfiguration->expects($this->any())
            ->method('getTimesheetLongRunningDuration')
            ->willReturn(0);

        $this->systemConfiguration->expects($this->any())
            ->method('isBreakTimeEnabled')
            ->willReturn(false);
    }

    public function testDefaultOptionsDoNotAllowActivityCreation(): void
    {
        $form = $this->factory->create(TimesheetEditForm::class, null, [
            'user' => $this->createMock(\App\Entity\User::class),
        ]);

        $activityForm = $form->get('activity');
        $view = $activityForm->createView();

        // By default, activity creation should not be allowed
        self::assertArrayNotHasKey('data-create', $view->vars['attr']);
    }

    public function testWithCreateActivityOptionEnabled(): void
    {
        $form = $this->factory->create(TimesheetEditForm::class, null, [
            'user' => $this->createMock(\App\Entity\User::class),
            'create_activity' => true,
        ]);

        $activityForm = $form->get('activity');
        $view = $activityForm->createView();

        // When create_activity is true, the data-create attribute should be present
        // and should contain the full URL, not just the route name
        self::assertArrayHasKey('data-create', $view->vars['attr']);
        // The URL should be the absolute path for /api/activities
        self::assertStringContainsString('/api/activities', $view->vars['attr']['data-create']);
    }

    public function testWithCreateActivityOptionDisabled(): void
    {
        $form = $this->factory->create(TimesheetEditForm::class, null, [
            'user' => $this->createMock(\App\Entity\User::class),
            'create_activity' => false,
        ]);

        $activityForm = $form->get('activity');
        $view = $activityForm->createView();

        // When create_activity is false, the data-create attribute should not be present
        self::assertArrayNotHasKey('data-create', $view->vars['attr']);
    }

    public function testFormHasExpectedFields(): void
    {
        $form = $this->factory->create(TimesheetEditForm::class, null, [
            'user' => $this->createMock(\App\Entity\User::class),
        ]);

        self::assertTrue($form->has('begin_date'));
        self::assertTrue($form->has('begin_time'));
        self::assertTrue($form->has('end_time'));
        self::assertTrue($form->has('duration'));
        self::assertTrue($form->has('customer'));
        self::assertTrue($form->has('project'));
        self::assertTrue($form->has('activity'));
        self::assertTrue($form->has('description'));
        self::assertTrue($form->has('tags'));
        self::assertTrue($form->has('fixedRate'));
        self::assertTrue($form->has('hourlyRate'));
        self::assertTrue($form->has('metaFields'));
        self::assertTrue($form->has('billableMode'));
    }

    public function testFormOptionsAreSetCorrectly(): void
    {
        $form = $this->factory->create(TimesheetEditForm::class, null, [
            'user' => $this->createMock(\App\Entity\User::class),
        ]);

        $config = $form->getConfig();

        self::assertEquals(\App\Entity\Timesheet::class, $config->getOption('data_class'));
        self::assertTrue($config->getOption('csrf_protection'));
        self::assertFalse($config->getOption('create_activity'));
        self::assertTrue($config->getOption('include_billable'));
        self::assertTrue($config->getOption('include_rate'));
        self::assertTrue($config->getOption('allow_begin_datetime'));
        self::assertTrue($config->getOption('allow_end_datetime'));
    }
}
