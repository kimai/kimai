<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Form\Type\DurationType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @covers \App\Form\Type\DurationType
 */
class DurationTypeTest extends TypeTestCase
{
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
    public function testSubmitValidData($value, $expected): void
    {
        $data = ['duration' => $value];
        $model = new TypeTestModel(['duration' => 3600]);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('duration', DurationType::class);
        $form = $form->getForm();

        $expected = new TypeTestModel([
            'duration' => $expected
        ]);

        $form->submit($data);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $model);
    }

    public function testPresetPopulatesView(): void
    {
        $view = $this->factory->create(DurationType::class, 3600, [
            'preset_minutes' => 15,
            'preset_hours' => 5,
        ])->createView();

        self::assertArrayHasKey('duration_presets', $view->vars);
        self::assertCount(20, $view->vars['duration_presets']);
        self::assertEquals('0:30', $view->vars['duration_presets'][1]);
        self::assertEquals('4:45', $view->vars['duration_presets'][18]);
    }

    public function testPresetsAreNotGeneratedOnMissingHours(): void
    {
        $view = $this->factory->create(DurationType::class, 3600, [
            'preset_minutes' => 5,
        ])->createView();

        self::assertArrayNotHasKey('duration_presets', $view->vars);
    }

    public function testPresetsAreNotGeneratedOnMissingMinutes(): void
    {
        $view = $this->factory->create(DurationType::class, 3600, [
            'preset_hours' => 5,
        ])->createView();

        self::assertArrayNotHasKey('duration_presets', $view->vars);
    }

    public function testPresetsAreNotGeneratedOnNegativeMinutes(): void
    {
        $view = $this->factory->create(DurationType::class, 3600, [
            'preset_minutes' => -1,
            'preset_hours' => 5,
        ])->createView();

        self::assertArrayNotHasKey('duration_presets', $view->vars);
    }

    public function testPresetsAreNotGeneratedOnNegativeHours(): void
    {
        $view = $this->factory->create(DurationType::class, 3600, [
            'preset_minutes' => 5,
            'preset_hours' => -1,
        ])->createView();

        self::assertArrayNotHasKey('duration_presets', $view->vars);
    }

    public function testHasDurationInputClass(): void
    {
        $view = $this->factory->create(DurationType::class, 3600, [
            'attr' => ['class' => 'testing']
        ])->createView();

        self::assertArrayHasKey('class', $view->vars['attr']);
        self::assertStringContainsString('duration-input testing', $view->vars['attr']['class']);
    }
}
