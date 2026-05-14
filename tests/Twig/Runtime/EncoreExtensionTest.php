<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Runtime;

use App\Twig\Runtime\EncoreExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Twig\Error\RuntimeError;

#[CoversClass(EncoreExtension::class)]
class EncoreExtensionTest extends TestCase
{
    protected function getSut(array $files = [], bool $expectsReset = true): EncoreExtension
    {
        $entryLookup = $this->createMock(EntrypointLookupInterface::class);
        $entryLookup->expects($this->any())->method('getCssFiles')->willReturn($files);
        $entryLookup->expects($expectsReset ? $this->once() : $this->never())->method('reset');

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
        self::assertEquals($css, $sut->getEncoreEntryCssSource('invoice'));
    }

    public function testGetEncoreEntryCssSourceIgnoresNonCssFiles(): void
    {
        $sut = $this->getSut(['test.css', 'test.js', 'test1.css', 'build/app.css.map']);
        $css = 'body { margin: 0; }p
{
    color: red; font-style: italic; }';

        self::assertEquals($css, $sut->getEncoreEntryCssSource('invoice-pdf'));
    }

    public function testGetEncoreEntryCssSourceIgnoresDirectoryTraversalPaths(): void
    {
        $sut = $this->getSut(['../composer.json', 'test.css', 'foo/../test1.css', '../ContextTest.php']);

        self::assertSame('body { margin: 0; }', $sut->getEncoreEntryCssSource('export-pdf'));
    }

    public function testGetEncoreEntryCssSourceRejectsUnknownPackage(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Unknown CSS package requested: blub');

        $sut = $this->getSut([], false);
        $sut->getEncoreEntryCssSource('blub');
    }
}
