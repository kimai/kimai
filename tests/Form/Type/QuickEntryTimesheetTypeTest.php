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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(QuickEntryTimesheetType::class)]
class QuickEntryTimesheetTypeTest extends TypeTestCase
{
    /**
     * @return FormExtensionInterface[]
     */
    protected function getExtensions(): array
    {
        $auth = $this->createMock(Security::class);
        $auth->method('getUser')->willReturn(new User());
        $auth->method('isGranted')->willReturn(true);

        $type = new QuickEntryTimesheetType($auth);

        return [
            new PreloadedExtension([$type], []),
        ];
    }

    public static function getTestData()
    {
        yield [4.5, 0, 16200];
        yield ['4,5', 0, 16200];
        yield ['4:30', 0, 16200];
        yield ['4h30m', 0, 16200];
        yield ['4h30m', 1800, 16200]; // it is important, that the duration does not change with breaks
    }

    #[DataProvider('getTestData')]
    public function testSubmitValidData(string|float $value, int $break, int $expectedDuration): void
    {
        $data = ['duration' => $value];

        $model = $this->createDefaultModel($expectedDuration, $break);

        $form = $this->factory->create(QuickEntryTimesheetType::class, $model);

        $form->submit($data);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($expectedDuration, $model->getDuration());
        self::assertEquals($expectedDuration, $model->getDuration(true));
        self::assertEquals($break, $model->getBreak());
    }

    private function createDefaultModel(int $duration = 0, int $break = 0): Timesheet
    {
        $begin = new \DateTime('2020-02-15 12:30:00');
        $end = new \DateTime('2020-02-15 14:00:00');

        $model = new Timesheet();
        $model->setBegin($begin);
        //$model->setDuration($duration);
        $model->setEnd($end);
        $model->setBreak($break);

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
