<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Form\Helper\ProjectHelper;
use App\Form\Type\ChoiceOrderedType;
use App\Form\Type\ProjectTypePatternType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(ProjectTypePatternType::class)]
class ProjectTypePatternTypeTest extends TypeTestCase
{
    /**
     * @return ProjectTypePatternType[]
     */
    protected function getTypes(): array
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return [
            new ProjectTypePatternType($translator)
        ];
    }

    /**
     * @return FormInterface<mixed>
     */
    private function createForm(string $pattern): FormInterface
    {
        $form = $this->factory->createBuilder(FormType::class, new TypeTestModel(['pattern' => $pattern]));
        $form->add('pattern', ProjectTypePatternType::class);

        return $form->getForm();
    }

    public function testGetParent(): void
    {
        $sut = new ProjectTypePatternType($this->createMock(TranslatorInterface::class));
        self::assertEquals(ChoiceOrderedType::class, $sut->getParent());
    }

    public function testDefaultOptions(): void
    {
        $config = $this->createForm('')->get('pattern')->getConfig();

        self::assertEquals('choice_pattern', $config->getOption('label'));
        self::assertTrue($config->getOption('multiple'));
        self::assertTrue($config->getOption('order'));
        self::assertEquals([
            'project_number' => ProjectHelper::PATTERN_NUMBER,
            'orderNumber' => ProjectHelper::PATTERN_ORDERNUMBER,
            'name' => ProjectHelper::PATTERN_NAME,
            'description' => ProjectHelper::PATTERN_COMMENT,
            'customer' => ProjectHelper::PATTERN_CUSTOMER,
            'project_start-project_end' => ProjectHelper::PATTERN_DATERANGE,
        ], $config->getOption('choices'));
    }

    /**
     * @return iterable<array{0: string, 1: array<string>, 2: string}>
     */
    public static function getSubmitData(): iterable
    {
        $spacer = ProjectHelper::PATTERN_SPACER;

        // order of the choice list
        yield ['', [ProjectHelper::PATTERN_NUMBER, ProjectHelper::PATTERN_NAME], ProjectHelper::PATTERN_NUMBER . $spacer . ProjectHelper::PATTERN_NAME];
        // reverse order
        yield ['', [ProjectHelper::PATTERN_NAME, ProjectHelper::PATTERN_NUMBER], ProjectHelper::PATTERN_NAME . $spacer . ProjectHelper::PATTERN_NUMBER];
        // existing data does not change the submitted order
        yield [ProjectHelper::PATTERN_NUMBER . $spacer . ProjectHelper::PATTERN_NAME, [ProjectHelper::PATTERN_CUSTOMER, ProjectHelper::PATTERN_NAME, ProjectHelper::PATTERN_NUMBER], ProjectHelper::PATTERN_CUSTOMER . $spacer . ProjectHelper::PATTERN_NAME . $spacer . ProjectHelper::PATTERN_NUMBER];
        yield [ProjectHelper::PATTERN_NUMBER . $spacer . ProjectHelper::PATTERN_NAME, [ProjectHelper::PATTERN_NAME], ProjectHelper::PATTERN_NAME];
        // nothing selected
        yield [ProjectHelper::PATTERN_NUMBER . $spacer . ProjectHelper::PATTERN_NAME, [], ''];
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
        $form->submit(['pattern' => [ProjectHelper::PATTERN_COMMENT, '{foo}', ProjectHelper::PATTERN_NAME]]);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
        self::assertSame(ProjectHelper::PATTERN_COMMENT . ProjectHelper::PATTERN_SPACER . ProjectHelper::PATTERN_NAME, $form->get('pattern')->getData());
    }

    public function testViewContainsOrderedItems(): void
    {
        $view = $this->createForm(ProjectHelper::PATTERN_COMMENT . ProjectHelper::PATTERN_SPACER . ProjectHelper::PATTERN_NAME)->createView();

        self::assertSame(
            json_encode([ProjectHelper::PATTERN_COMMENT, ProjectHelper::PATTERN_NAME]),
            $view['pattern']->vars['attr']['data-items']
        );
    }

    public function testViewWithoutSelection(): void
    {
        $view = $this->createForm('')->createView();

        self::assertArrayNotHasKey('data-items', $view['pattern']->vars['attr']);
    }
}
