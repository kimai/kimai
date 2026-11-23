<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Form\Helper\ActivityHelper;
use App\Form\Type\ActivityTypePatternType;
use App\Form\Type\ChoiceOrderedType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(ActivityTypePatternType::class)]
class ActivityTypePatternTypeTest extends TypeTestCase
{
    /**
     * @return ActivityTypePatternType[]
     */
    protected function getTypes(): array
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return [
            new ActivityTypePatternType($translator)
        ];
    }

    /**
     * @return FormInterface<mixed>
     */
    private function createForm(string $pattern): FormInterface
    {
        $form = $this->factory->createBuilder(FormType::class, new TypeTestModel(['pattern' => $pattern]));
        $form->add('pattern', ActivityTypePatternType::class);

        return $form->getForm();
    }

    public function testGetParent(): void
    {
        $sut = new ActivityTypePatternType($this->createMock(TranslatorInterface::class));
        self::assertEquals(ChoiceOrderedType::class, $sut->getParent());
    }

    public function testDefaultOptions(): void
    {
        $config = $this->createForm('')->get('pattern')->getConfig();

        self::assertEquals('choice_pattern', $config->getOption('label'));
        self::assertTrue($config->getOption('multiple'));
        self::assertTrue($config->getOption('order'));
        self::assertEquals([
            'activity_number' => ActivityHelper::PATTERN_NUMBER,
            'name' => ActivityHelper::PATTERN_NAME,
            'description' => ActivityHelper::PATTERN_COMMENT,
        ], $config->getOption('choices'));
    }

    /**
     * @return iterable<array{0: string, 1: array<string>, 2: string}>
     */
    public static function getSubmitData(): iterable
    {
        $spacer = ActivityHelper::PATTERN_SPACER;

        // order of the choice list
        yield ['', [ActivityHelper::PATTERN_NUMBER, ActivityHelper::PATTERN_NAME], ActivityHelper::PATTERN_NUMBER . $spacer . ActivityHelper::PATTERN_NAME];
        // reverse order
        yield ['', [ActivityHelper::PATTERN_NAME, ActivityHelper::PATTERN_NUMBER], ActivityHelper::PATTERN_NAME . $spacer . ActivityHelper::PATTERN_NUMBER];
        // existing data does not change the submitted order
        yield [ActivityHelper::PATTERN_NUMBER . $spacer . ActivityHelper::PATTERN_NAME, [ActivityHelper::PATTERN_COMMENT, ActivityHelper::PATTERN_NAME, ActivityHelper::PATTERN_NUMBER], ActivityHelper::PATTERN_COMMENT . $spacer . ActivityHelper::PATTERN_NAME . $spacer . ActivityHelper::PATTERN_NUMBER];
        yield [ActivityHelper::PATTERN_NUMBER . $spacer . ActivityHelper::PATTERN_NAME, [ActivityHelper::PATTERN_NAME], ActivityHelper::PATTERN_NAME];
        // nothing selected
        yield [ActivityHelper::PATTERN_NUMBER . $spacer . ActivityHelper::PATTERN_NAME, [], ''];
    }

    /**
     * @param array<string> $submitted
     */
    #[DataProvider('getSubmitData')]
    public function testSubmitKeepsOrder(string $existing, array $submitted, string $expected): void
    {
        $form = $this->createForm($existing);
        $form->submit(['pattern' => $submitted]);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertSame($expected, $form->get('pattern')->getData());
    }

    public function testSubmitUnknownValues(): void
    {
        $form = $this->createForm('');
        $form->submit(['pattern' => [ActivityHelper::PATTERN_COMMENT, '{foo}', ActivityHelper::PATTERN_NAME]]);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
        self::assertSame(ActivityHelper::PATTERN_COMMENT . ActivityHelper::PATTERN_SPACER . ActivityHelper::PATTERN_NAME, $form->get('pattern')->getData());
    }

    public function testViewContainsOrderedItems(): void
    {
        $view = $this->createForm(ActivityHelper::PATTERN_COMMENT . ActivityHelper::PATTERN_SPACER . ActivityHelper::PATTERN_NAME)->createView();

        self::assertSame(
            json_encode([ActivityHelper::PATTERN_COMMENT, ActivityHelper::PATTERN_NAME]),
            $view['pattern']->vars['attr']['data-items']
        );
    }

    public function testViewWithoutSelection(): void
    {
        $view = $this->createForm('')->createView();

        self::assertArrayNotHasKey('data-items', $view['pattern']->vars['attr']);
    }
}
