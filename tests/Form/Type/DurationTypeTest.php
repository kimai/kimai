<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Form\Type\DurationType;
use App\Validator\Constraints\Duration as DurationConstraint;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

#[CoversClass(DurationType::class)]
class DurationTypeTest extends TypeTestCase
{
    public static function getTestData()
    {
        yield [4.5, 16200];
        yield ['4,5', 16200];
        yield ['4:30', 16200];
        yield ['4h30m', 16200];
    }

    #[DataProvider('getTestData')]
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

        self::assertTrue($form->isSynchronized());
        self::assertEquals($expected, $model);
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

    public function testDefaultParseMode(): void
    {
        $view = $this->factory->create(DurationType::class, 3600)->createView();

        self::assertArrayHasKey('data-duration-mode', $view->vars['attr']);
        self::assertEquals(DurationType::PARSE_MODE_DEFAULT, $view->vars['attr']['data-duration-mode']);
    }

    public function testIntegerMinutesParseMode(): void
    {
        $view = $this->factory->create(DurationType::class, 3600, [
            'parse_mode' => DurationType::PARSE_MODE_INTEGER_MINUTES,
        ])->createView();

        self::assertEquals(DurationType::PARSE_MODE_INTEGER_MINUTES, $view->vars['attr']['data-duration-mode']);
    }

    public function testHasValidationPattern(): void
    {
        $view = $this->factory->create(DurationType::class, 3600)->createView();

        self::assertArrayHasKey('pattern', $view->vars['attr']);
        self::assertEquals((new DurationConstraint())->getHtmlPattern(), $view->vars['attr']['pattern']);
    }

    public function testValidationPatternCanBeOverwritten(): void
    {
        $view = $this->factory->create(DurationType::class, 3600, [
            'attr' => ['pattern' => '[0-9]+']
        ])->createView();

        self::assertEquals('[0-9]+', $view->vars['attr']['pattern']);
    }

    public function testNegativeValuesAreNotAllowedByDefault(): void
    {
        $view = $this->factory->create(DurationType::class, 3600)->createView();

        self::assertArrayNotHasKey('data-duration-negative', $view->vars['attr']);
    }

    public function testAllowNegativeValues(): void
    {
        $view = $this->factory->create(DurationType::class, 3600, [
            'allow_negative' => true,
        ])->createView();

        self::assertEquals('1', $view->vars['attr']['data-duration-negative']);
    }

    public function testSubmitNegativeValue(): void
    {
        $model = new TypeTestModel(['duration' => 3600]);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('duration', DurationType::class, ['allow_negative' => true]);
        $form = $form->getForm();

        $form->submit(['duration' => '-1:30']);

        self::assertTrue($form->isSynchronized());
        self::assertEquals(new TypeTestModel(['duration' => -5400]), $model);
    }

    public function testInvalidParseModeThrowsException(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->factory->create(DurationType::class, 3600, [
            'parse_mode' => 'foo',
        ]);
    }
}
