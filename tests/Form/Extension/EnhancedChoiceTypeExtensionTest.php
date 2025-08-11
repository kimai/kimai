<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Extension;

use App\Form\Extension\EnhancedChoiceTypeExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[CoversClass(EnhancedChoiceTypeExtension::class)]
class EnhancedChoiceTypeExtensionTest extends TestCase
{
    public function testExtendedTypes(): void
    {
        self::assertEquals([EntityType::class, ChoiceType::class], EnhancedChoiceTypeExtension::getExtendedTypes());
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $sut = new EnhancedChoiceTypeExtension();
        $sut->configureOptions($resolver);
        self::assertEquals(['selectpicker', 'width', 'search', 'order'], $resolver->getDefinedOptions());
        self::assertTrue($resolver->hasDefault('selectpicker'));
        self::assertTrue($resolver->hasDefault('width'));
        self::assertTrue($resolver->hasDefault('search'));
        self::assertTrue($resolver->hasDefault('order'));
        self::assertFalse($resolver->isRequired('selectpicker'));
        self::assertFalse($resolver->isRequired('width'));
        self::assertFalse($resolver->isRequired('search'));

        $result = $resolver->resolve([]);
        self::assertEquals(['selectpicker' => true, 'width' => '100%', 'search' => true, 'order' => false], $result);
    }

    public static function getTestData(): iterable
    {
        yield [
            ['expanded' => true],
            ['value' => null, 'attr' => []]
        ];

        yield [
            ['multiple' => false, 'width' => false, 'search' => true, 'selectpicker' => true, 'order', 'required' => false],
            ['value' => null, 'attr' => ['class' => 'selectpicker']]
        ];

        yield [
            ['multiple' => false, 'width' => '100%', 'search' => true, 'required' => false, 'order' => true],
            ['value' => null, 'attr' => ['class' => 'selectpicker', 'data-width' => '100%', 'data-order' => 1]]
        ];

        yield [
            ['multiple' => true, 'width' => '50%', 'search' => false, 'required' => true, 'attr' => []],
            ['value' => null, 'attr' => ['size' => 1, 'class' => 'selectpicker', 'data-width' => '50%', 'data-disable-search' => 1, 'required' => 'required', 'placeholder' => '']]
        ];
    }

    #[DataProvider('getTestData')]
    public function testBuildView(array $options, array $expected): void
    {
        $sut = new EnhancedChoiceTypeExtension();
        $view = new FormView();
        $form = $this->createMock(FormInterface::class);

        $sut->buildView($view, $form, $options);
        self::assertEquals($expected, $view->vars);
    }
}
