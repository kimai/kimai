<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Runtime;

use App\Twig\Runtime\EncoreExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;

/**
 * @covers \App\Twig\Runtime\EncoreExtension
 */
class EncoreExtensionTest extends TestCase
{
    protected function getSut(array $files = []): EncoreExtension
    {
        $entryLookup = $this->createMock(EntrypointLookupInterface::class);
        $entryLookup->expects($this->any())->method('getCssFiles')->willReturn($files);

        $container = new Container(new ParameterBag([]));
        $container->set(EntrypointLookupInterface::class, $entryLookup);

        return new EncoreExtension($container, __DIR__ . '/../');
    }

    public function testGetSubscribedServices(): void
    {
        self::assertEquals([EntrypointLookupInterface::class], EncoreExtension::getSubscribedServices());
    }

    public function testGetEncoreEntryCssSource(): void
    {
        $sut = $this->getSut(['test.css', 'test1.css']);
        $css = 'body { margin: 0; }p
{
    color: red; font-style: italic; }';
        self::assertEquals($css, $sut->getEncoreEntryCssSource('blub'));
    }
}
