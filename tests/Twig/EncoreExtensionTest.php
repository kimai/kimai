<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Twig\EncoreExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Twig\TwigFunction;

/**
 * @covers \App\Twig\EncoreExtension
 */
class EncoreExtensionTest extends TestCase
{
    protected function getSut(array $files = []): EncoreExtension
    {
        $entryLookup = $this->createMock(EntrypointLookupInterface::class);
        $entryLookup->expects($this->any())->method('getCssFiles')->willReturn($files);

        $container = new Container(new ParameterBag([]));
        $container->set(EntrypointLookupInterface::class, $entryLookup);

        return new EncoreExtension($container, __DIR__);
    }

    public function testGetSubscribedServices()
    {
        self::assertEquals([EntrypointLookupInterface::class], EncoreExtension::getSubscribedServices());
    }

    public function testGetFunctions()
    {
        $functions = ['encore_entry_css_source'];
        $sut = $this->getSut();
        $twigFunctions = $sut->getFunctions();
        self::assertCount(\count($functions), $twigFunctions);
        $i = 0;
        /** @var TwigFunction $filter */
        foreach ($twigFunctions as $filter) {
            self::assertInstanceOf(TwigFunction::class, $filter);
            self::assertEquals($functions[$i++], $filter->getName());
        }
    }

    public function testGetEncoreEntryCssSource()
    {
        $sut = $this->getSut(['test.css', 'test1.css']);
        $css = 'body { margin: 0; }p
{
    color: red; font-style: italic; }';
        self::assertEquals($css, $sut->getEncoreEntryCssSource('blub'));
    }
}
