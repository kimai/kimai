<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Form\Type\QuickEntryTimesheetType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @covers \App\Form\Type\QuickEntryTimesheetType
 */
class QuickEntryTimesheetTypeTest extends TypeTestCase
{
    protected function getExtensions()
    {
        $auth = $this->createMock(Security::class);
        $auth->method('getUser')->willReturn(new User());
        $auth->method('isGranted')->willReturn(true);

        $type = new QuickEntryTimesheetType($auth);

        return [
            new PreloadedExtension([$type], []),
        ];
    }

    public function getTestData()
    {
        yield [4.5, 16200];
        yield ['4,5', 16200];
        yield ['4:30', 16200];
        yield ['4h30m', 16200];
    }

    /**
     * @dataProvider getTestData
     */
    public function testSubmitValidData($value, $expectedDuration): void
    {
        $data = ['duration' => $value];

        $model = $this->createDefaultModel();

        $form = $this->factory->create(QuickEntryTimesheetType::class, $model);

        $form->submit($data);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedDuration, $model->getDuration());
        $this->assertEquals($expectedDuration, $model->getDuration(true));
    }

    private function createDefaultModel(): Timesheet
    {
        $begin = new \DateTime('2020-02-15 12:30:00');
        $end = new \DateTime('2020-02-15 14:00:00');

        $model = new Timesheet();
        $model->setBegin($begin);
        $model->setEnd($end);

        return $model;
    }

    public function testPresetPopulatesView(): void
    {
        $view = $this->factory->create(QuickEntryTimesheetType::class, $this->createDefaultModel(), [
            'duration_minutes' => 15,
            'duration_hours' => 5,
        ])->createView();

        $vars = $view->children['duration']->vars;

        self::assertArrayHasKey('duration_presets', $vars);
        self::assertCount(20, $vars['duration_presets']);
        self::assertEquals('0:30', $vars['duration_presets'][1]);
        self::assertEquals('4:45', $vars['duration_presets'][18]);
    }

    public function testPresetsAreNotGeneratedOnMissingHours(): void
    {
        $view = $this->factory->create(QuickEntryTimesheetType::class, $this->createDefaultModel())->createView();

        $vars = $view->children['duration']->vars;

        self::assertArrayNotHasKey('duration_presets', $vars);
    }

    public function testPresetsAreNotGeneratedOnMissingMinutes(): void
    {
        $view = $this->factory->create(QuickEntryTimesheetType::class, $this->createDefaultModel(), [
            'duration_hours' => 5,
        ])->createView();

        $vars = $view->children['duration']->vars;

        self::assertArrayNotHasKey('duration_presets', $vars);
    }

    public function testPresetsAreNotGeneratedOnNegativeMinutes(): void
    {
        $view = $this->factory->create(QuickEntryTimesheetType::class, $this->createDefaultModel(), [
            'duration_minutes' => -1,
            'duration_hours' => 5,
        ])->createView();

        $vars = $view->children['duration']->vars;

        self::assertArrayNotHasKey('duration_presets', $vars);
    }

    public function testPresetsAreNotGeneratedOnNegativeHours(): void
    {
        $view = $this->factory->create(QuickEntryTimesheetType::class, $this->createDefaultModel(), [
            'duration_minutes' => 5,
            'duration_hours' => -1,
        ])->createView();

        $vars = $view->children['duration']->vars;

        self::assertArrayNotHasKey('duration_presets', $vars);
    }
}
