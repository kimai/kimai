<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget;

use App\Repository\WidgetRepository;
use App\Widget\Renderer\SimpleWidgetRenderer;
use App\Widget\Type\Counter;
use App\Widget\Type\More;
use App\Widget\WidgetService;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @covers \App\Widget\WidgetService
 */
class WidgetServiceTest extends TestCase
{
    public function testConstruct()
    {
        $repository = $this->getMockBuilder(WidgetRepository::class)->disableOriginalConstructor()->getMock();

        $sut = new WidgetService($repository, []);
        self::assertFalse($sut->hasWidget('sdfsdf'));
        self::assertCount(0, $sut->getRenderer());

        $sut = new WidgetService($repository, [
            new SimpleWidgetRenderer(new Environment(new FilesystemLoader()))
        ]);
        self::assertCount(1, $sut->getRenderer());
    }

    public function testFindRenderer()
    {
        $repository = $this->getMockBuilder(WidgetRepository::class)->disableOriginalConstructor()->getMock();

        $renderer = new SimpleWidgetRenderer(new Environment(new FilesystemLoader()));
        $sut = new WidgetService($repository, [$renderer]);
        $sut->addRenderer(new SimpleWidgetRenderer(new Environment(new FilesystemLoader())));

        self::assertCount(2, $sut->getRenderer());

        $found = $sut->findRenderer(new More());
        self::assertSame($renderer, $found);
    }

    /**
     * @expectedException \App\Widget\WidgetException
     * @expectedExceptionMessage No renderer available for widget "App\Widget\Type\More"
     */
    public function testFindRendererThrowsException()
    {
        $repository = $this->getMockBuilder(WidgetRepository::class)->disableOriginalConstructor()->getMock();

        $sut = new WidgetService($repository, []);
        $sut->findRenderer(new More());
    }

    public function testHasAndGetWidget()
    {
        $widget = new More();

        $repository = $this->getMockBuilder(WidgetRepository::class)->disableOriginalConstructor()->setMethods(['has', 'get'])->getMock();
        $repository->expects($this->once())->method('has')->willReturn(true);
        $repository->expects($this->once())->method('get')->willReturn($widget);

        $sut = new WidgetService($repository, []);
        $this->assertTrue($sut->hasWidget('sdfsdf'));
        $this->assertSame($widget, $sut->getWidget('sdfsdf'));
    }
}
