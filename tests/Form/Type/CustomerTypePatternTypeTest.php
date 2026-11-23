<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Form\Helper\CustomerHelper;
use App\Form\Type\ChoiceOrderedType;
use App\Form\Type\CustomerTypePatternType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(CustomerTypePatternType::class)]
class CustomerTypePatternTypeTest extends TypeTestCase
{
    /**
     * @return CustomerTypePatternType[]
     */
    protected function getTypes(): array
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return [
            new CustomerTypePatternType($translator)
        ];
    }

    /**
     * @return FormInterface<mixed>
     */
    private function createForm(string $pattern): FormInterface
    {
        $form = $this->factory->createBuilder(FormType::class, new TypeTestModel(['pattern' => $pattern]));
        $form->add('pattern', CustomerTypePatternType::class);

        return $form->getForm();
    }

    public function testGetParent(): void
    {
        $sut = new CustomerTypePatternType($this->createMock(TranslatorInterface::class));
        self::assertEquals(ChoiceOrderedType::class, $sut->getParent());
    }

    public function testDefaultOptions(): void
    {
        $config = $this->createForm('')->get('pattern')->getConfig();

        self::assertEquals('choice_pattern', $config->getOption('label'));
        self::assertTrue($config->getOption('multiple'));
        self::assertTrue($config->getOption('order'));
        self::assertEquals([
            'number' => CustomerHelper::PATTERN_NUMBER,
            'name' => CustomerHelper::PATTERN_NAME,
            'company' => CustomerHelper::PATTERN_COMPANY,
            'description' => CustomerHelper::PATTERN_COMMENT,
        ], $config->getOption('choices'));
    }

    /**
     * @return iterable<array{0: string, 1: array<string>, 2: string}>
     */
    public static function getSubmitData(): iterable
    {
        $spacer = CustomerHelper::PATTERN_SPACER;

        // order of the choice list
        yield ['', [CustomerHelper::PATTERN_NUMBER, CustomerHelper::PATTERN_NAME], CustomerHelper::PATTERN_NUMBER . $spacer . CustomerHelper::PATTERN_NAME];
        // reverse order
        yield ['', [CustomerHelper::PATTERN_NAME, CustomerHelper::PATTERN_NUMBER], CustomerHelper::PATTERN_NAME . $spacer . CustomerHelper::PATTERN_NUMBER];
        // existing data does not change the submitted order
        yield [CustomerHelper::PATTERN_NUMBER . $spacer . CustomerHelper::PATTERN_NAME, [CustomerHelper::PATTERN_COMPANY, CustomerHelper::PATTERN_NAME, CustomerHelper::PATTERN_NUMBER], CustomerHelper::PATTERN_COMPANY . $spacer . CustomerHelper::PATTERN_NAME . $spacer . CustomerHelper::PATTERN_NUMBER];
        yield [CustomerHelper::PATTERN_NUMBER . $spacer . CustomerHelper::PATTERN_NAME, [CustomerHelper::PATTERN_NAME], CustomerHelper::PATTERN_NAME];
        // nothing selected
        yield [CustomerHelper::PATTERN_NUMBER . $spacer . CustomerHelper::PATTERN_NAME, [], ''];
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
        $form->submit(['pattern' => [CustomerHelper::PATTERN_COMMENT, '{foo}', CustomerHelper::PATTERN_NAME]]);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
        self::assertSame(CustomerHelper::PATTERN_COMMENT . CustomerHelper::PATTERN_SPACER . CustomerHelper::PATTERN_NAME, $form->get('pattern')->getData());
    }

    public function testViewContainsOrderedItems(): void
    {
        $view = $this->createForm(CustomerHelper::PATTERN_COMMENT . CustomerHelper::PATTERN_SPACER . CustomerHelper::PATTERN_NAME)->createView();

        self::assertSame(
            json_encode([CustomerHelper::PATTERN_COMMENT, CustomerHelper::PATTERN_NAME]),
            $view['pattern']->vars['attr']['data-items']
        );
    }

    public function testViewWithoutSelection(): void
    {
        $view = $this->createForm('')->createView();

        self::assertArrayNotHasKey('data-items', $view['pattern']->vars['attr']);
    }
}
