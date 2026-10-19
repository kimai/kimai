<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Form\Type\ChoiceOrderedType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

#[CoversClass(ChoiceOrderedType::class)]
class ChoiceOrderedTypeTest extends TypeTestCase
{
    private const CHOICES = ['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd'];

    /**
     * @param array<string, mixed> $model
     * @param array<string, mixed> $options
     * @return FormInterface<mixed>
     */
    private function createForm(array $model, array $options = ['choices' => self::CHOICES]): FormInterface
    {
        $form = $this->factory->createBuilder(FormType::class, new TypeTestModel($model));
        $form->add('items', ChoiceOrderedType::class, $options);

        return $form->getForm();
    }

    public function testGetParent(): void
    {
        $sut = new ChoiceOrderedType();
        self::assertEquals(ChoiceType::class, $sut->getParent());
    }

    public function testDefaultOptions(): void
    {
        $form = $this->createForm(['items' => []]);
        $config = $form->get('items')->getConfig();

        self::assertTrue($config->getOption('multiple'));
        self::assertTrue($config->getOption('order'));
    }

    public function testSingleChoiceIsNotAllowed(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->createForm(['items' => null], ['choices' => self::CHOICES, 'multiple' => false]);
    }

    /**
     * @return iterable<array{0: array<string>, 1: array<mixed>, 2: array<string>}>
     */
    public static function getSubmitData(): iterable
    {
        // order of the choice list
        yield [[], ['a', 'b', 'c'], ['a', 'b', 'c']];
        // reverse order
        yield [[], ['d', 'c', 'b', 'a'], ['d', 'c', 'b', 'a']];
        // existing data does not change the submitted order
        yield [['a', 'b', 'c'], ['c', 'a'], ['c', 'a']];
        yield [['a', 'b', 'c'], ['c', 'd', 'b', 'a'], ['c', 'd', 'b', 'a']];
        yield [['a', 'b', 'c'], ['a', 'b', 'c'], ['a', 'b', 'c']];
        // duplicates are removed
        yield [[], ['b', 'a', 'b'], ['b', 'a']];
        // nothing selected
        yield [['a', 'b'], [], []];
    }

    /**
     * @param array<string> $existing
     * @param array<mixed> $submitted
     * @param array<string> $expected
     */
    #[DataProvider('getSubmitData')]
    public function testSubmitKeepsOrder(array $existing, array $submitted, array $expected): void
    {
        $form = $this->createForm(['items' => $existing]);
        $form->submit(['items' => $submitted]);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertSame($expected, $form->get('items')->getData());
    }

    public function testSubmitMissingFieldClearsSelection(): void
    {
        $form = $this->createForm(['items' => ['a', 'b']]);
        $form->submit([]);

        self::assertTrue($form->isSynchronized());
        self::assertSame([], $form->get('items')->getData());
    }

    public function testSubmitUnknownValues(): void
    {
        $form = $this->createForm(['items' => []]);
        $form->submit(['items' => ['c', 'x', 'a']]);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
        self::assertSame(['c', 'a'], $form->get('items')->getData());
    }

    public function testSubmitReturnsChoiceDataInsteadOfValues(): void
    {
        $form = $this->createForm(['items' => [1, 2]], ['choices' => ['One' => 1, 'Two' => 2, 'Three' => 3]]);
        $form->submit(['items' => ['3', '1']]);

        self::assertTrue($form->isSynchronized());
        self::assertSame([3, 1], $form->get('items')->getData());
    }

    public function testMultipleFieldsDoNotShareState(): void
    {
        $form = $this->factory->createBuilder(FormType::class, new TypeTestModel(['first' => ['a'], 'second' => ['b']]));
        $form->add('first', ChoiceOrderedType::class, ['choices' => self::CHOICES]);
        $form->add('second', ChoiceOrderedType::class, ['choices' => self::CHOICES]);
        $form = $form->getForm();

        $form->submit(['first' => ['d', 'a']]);

        self::assertSame(['d', 'a'], $form->get('first')->getData());
        self::assertSame([], $form->get('second')->getData());
    }

    public function testViewContainsOrderedItems(): void
    {
        $form = $this->createForm(['items' => ['c', 'a', 'd']]);
        $view = $form->createView();

        self::assertArrayHasKey('data-items', $view['items']->vars['attr']);
        self::assertSame('["c","a","d"]', $view['items']->vars['attr']['data-items']);
    }

    public function testViewContainsOrderedItemsAfterSubmit(): void
    {
        $form = $this->createForm(['items' => ['a', 'b']]);
        $form->submit(['items' => ['d', 'b']]);
        $view = $form->createView();

        self::assertSame('["d","b"]', $view['items']->vars['attr']['data-items']);
    }

    public function testViewContainsChoiceValues(): void
    {
        $form = $this->createForm(['items' => [3, 1]], ['choices' => ['One' => 1, 'Two' => 2, 'Three' => 3]]);
        $view = $form->createView();

        self::assertSame('["3","1"]', $view['items']->vars['attr']['data-items']);
    }

    public function testViewWithoutSelection(): void
    {
        $form = $this->createForm(['items' => []]);
        $view = $form->createView();

        self::assertArrayNotHasKey('data-items', $view['items']->vars['attr']);
    }
}
