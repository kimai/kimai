<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Twig\WidgetExtension;
use App\Widget\Type\Counter;
use App\Widget\WidgetInterface;
use App\Widget\WidgetRendererInterface;
use App\Widget\WidgetService;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

/**
 * @covers \App\Twig\WidgetExtension
 */
class WidgetExtensionTest extends TestCase
{
    protected function getSut($hasWidget = null, $getWidget = null, $renderer = null): WidgetExtension
    {
        $service = $this->getMockBuilder(WidgetService::class)->disableOriginalConstructor()->onlyMethods(['hasWidget', 'getWidget', 'findRenderer'])->getMock();
        if (null !== $hasWidget) {
            $service->expects($this->once())->method('hasWidget')->willReturn($hasWidget);
        }
        if (null !== $getWidget) {
            $service->expects($this->once())->method('getWidget')->willReturn($getWidget);
        }
        if (null !== $renderer) {
            $service->expects($this->once())->method('findRenderer')->willReturn($renderer);
        }

        return new WidgetExtension($service);
    }

    public function testGetFunctions()
    {
        $functions = ['render_widget'];
        $sut = $this->getSut();
        $twigFunctions = $sut->getFunctions();
        $this->assertCount(\count($functions), $twigFunctions);
        $i = 0;
        /** @var TwigFunction $function */
        foreach ($twigFunctions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
            $this->assertEquals($functions[$i++], $function->getName());
        }
    }

    public function testRenderWidgetForInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Widget must either implement WidgetInterface or be a string');

        $sut = $this->getSut();
        $sut->renderWidget(true);
    }

    public function testRenderWidgetForUnknownWidget()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown widget "test" requested');

        $sut = $this->getSut(false);
        $sut->renderWidget('test');
    }

    public function testRenderWidgetByString()
    {
        $widget = new Counter();
        $sut = $this->getSut(true, $widget, new TestRenderer());
        $options = ['foo' => 'bar', 'dataType' => 'blub'];
        $result = $sut->renderWidget('test', $options);
        $data = json_decode($result, true);
        $this->assertEquals($options, $data);
    }

    public function testRenderWidgetObject()
    {
        $widget = new Counter();
        $sut = $this->getSut(null, null, new TestRenderer());
        $options = ['foo' => 'bar', 'dataType' => 'blub'];
        $result = $sut->renderWidget($widget, $options);
        $data = json_decode($result, true);
        $this->assertEquals($options, $data);
    }
}

class TestRenderer implements WidgetRendererInterface
{
    public function supports(WidgetInterface $widget): bool
    {
        return true;
    }

    public function render(WidgetInterface $widget, array $options = []): string
    {
        return json_encode($widget->getOptions($options));
    }
}
