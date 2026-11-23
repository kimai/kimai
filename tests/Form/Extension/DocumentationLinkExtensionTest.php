<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Extension;

use App\Form\Extension\DocumentationLinkExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[CoversClass(DocumentationLinkExtension::class)]
class DocumentationLinkExtensionTest extends TestCase
{
    public function testExtendedTypes(): void
    {
        self::assertEquals([FormType::class], DocumentationLinkExtension::getExtendedTypes());
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $sut = new DocumentationLinkExtension();
        $sut->configureOptions($resolver);
        self::assertEquals(['docu_chapter'], $resolver->getDefinedOptions());
        self::assertTrue($resolver->hasDefault('docu_chapter'));
        self::assertFalse($resolver->isRequired('docu_chapter'));

        $result = $resolver->resolve(['docu_chapter' => 'foo']);
        self::assertEquals(['docu_chapter' => 'foo'], $result);

        $result = $resolver->resolve([]);
        self::assertEquals(['docu_chapter' => ''], $result);

        $this->expectException(InvalidOptionsException::class);
        $resolver->resolve(['docu_chapter' => true]);
    }

    public function testBuildView(): void
    {
        $sut = new DocumentationLinkExtension();
        $form = $this->createMock(FormInterface::class);

        $view = new FormView();
        $sut->buildView($view, $form, ['docu_chapter' => null]);
        self::assertEquals(['attr' => [], 'value' => null, 'docu_chapter' => null], $view->vars);

        $view = new FormView();
        $sut->buildView($view, $form, ['docu_chapter' => 'customers']);
        self::assertEquals(['attr' => [], 'value' => null, 'docu_chapter' => 'customers'], $view->vars);
    }
}
