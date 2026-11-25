<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Extension;

use App\Entity\Customer;
use App\Form\ChoiceList\LazyChoiceLoader;
use App\Form\ChoiceList\LazyChoiceTypeInterface;
use App\Form\Extension\LazyLoadExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[CoversClass(LazyLoadExtension::class)]
class LazyLoadExtensionTest extends TestCase
{
    private function createSut(): LazyLoadExtension
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(fn (string $route, array $params) => '/api/' . $route . '?' . http_build_query($params));

        return new LazyLoadExtension($urlGenerator);
    }

    /**
     * @param FormTypeInterface<mixed> $type
     * @return FormInterface<mixed>
     */
    private function createForm(FormTypeInterface $type): FormInterface
    {
        $resolvedType = $this->createMock(ResolvedFormTypeInterface::class);
        $resolvedType->method('getInnerType')->willReturn($type);
        $config = $this->createMock(FormConfigInterface::class);
        $config->method('getType')->willReturn($resolvedType);
        $form = $this->createMock(FormInterface::class);
        $form->method('getConfig')->willReturn($config);

        return $form;
    }

    /**
     * @return FormTypeInterface<mixed>
     */
    private function createLazyType(): FormTypeInterface
    {
        return new class() extends AbstractType implements LazyChoiceTypeInterface {
            public function getLazyLoadApi(array $options): array
            {
                return ['route' => 'projects', 'params' => ['visible' => 1], 'parent_param' => 'customer', 'parent' => $options['customers'], 'all_without_parent' => true];
            }
        };
    }

    public function testGetExtendedTypes(): void
    {
        self::assertEquals([EntityType::class], LazyLoadExtension::getExtendedTypes());
    }

    public function testConfigureOptionsWrapsTheChoiceLoader(): void
    {
        $loader = new CallbackChoiceLoader(fn () => []);
        $resolver = new OptionsResolver();
        $resolver->setDefault('choice_loader', $loader);
        $this->createSut()->configureOptions($resolver);

        self::assertSame($loader, $resolver->resolve()['choice_loader']);
        self::assertFalse($resolver->resolve()['lazy_load']);
        self::assertInstanceOf(LazyChoiceLoader::class, $resolver->resolve(['lazy_load' => true])['choice_loader']);
    }

    public function testBuildViewWithoutLazyLoad(): void
    {
        $view = new FormView();
        $this->createSut()->buildView($view, $this->createMock(FormInterface::class), ['lazy_load' => false]);

        self::assertArrayNotHasKey('data-lazy-url', $view->vars['attr']);
    }

    public function testBuildViewNeedsLazyChoiceType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "lazy_load" is not supported by form type ' . TextType::class);

        $this->createSut()->buildView(new FormView(), $this->createForm(new TextType()), ['lazy_load' => true]);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function getParents(): iterable
    {
        yield 'none' => [null, '/api/projects?visible=1'];
        yield 'empty' => [[], '/api/projects?visible=1'];
        yield 'id' => [13, '/api/projects?visible=1&customer=13'];
        yield 'numeric string' => ['13', '/api/projects?visible=1&customer=13'];
        yield 'list with one id' => [[13], '/api/projects?visible=1&customer=13'];
    }

    #[DataProvider('getParents')]
    public function testBuildViewRendersTheApiSearch(mixed $parent, string $url): void
    {
        $view = new FormView();
        $view->vars['attr'] = ['class' => 'foo'];
        $this->createSut()->buildView($view, $this->createForm($this->createLazyType()), ['lazy_load' => true, 'customers' => $parent]);

        self::assertEquals([
            'class' => 'foo',
            'data-lazy-url' => $url,
            'data-lazy-parent' => 'customer',
            'data-lazy-all-without-parent' => '1',
        ], $view->vars['attr']);
    }

    public function testBuildViewUsesTheIdOfAnEntity(): void
    {
        $customer = $this->createMock(Customer::class);
        $customer->method('getId')->willReturn(13);

        $view = new FormView();
        $this->createSut()->buildView($view, $this->createForm($this->createLazyType()), ['lazy_load' => true, 'customers' => [$customer]]);

        self::assertEquals('/api/projects?visible=1&customer=13', $view->vars['attr']['data-lazy-url']);
    }

    public function testBuildViewSupportsOneParentOnly(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "lazy_load" supports only one parent');

        $this->createSut()->buildView(new FormView(), $this->createForm($this->createLazyType()), ['lazy_load' => true, 'customers' => [1, 2]]);
    }
}
