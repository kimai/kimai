<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Renderer;

use App\Widget\Renderer\SimpleWidgetRenderer;
use App\Widget\Type\Counter;
use App\Widget\Type\More;
use App\Widget\Type\SimpleWidget;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

/**
 * @covers \App\Widget\Renderer\SimpleWidgetRenderer
 * @covers \App\Widget\Renderer\AbstractTwigRenderer
 */
class SimpleWidgetRendererTest extends TestCase
{
    public function testSupports()
    {
        $twig = $this->createMock(Environment::class);
        $sut = new SimpleWidgetRenderer($twig);
        self::assertTrue($sut->supports(new SimpleWidget()));
    }

    /**
     * @dataProvider getSimpleWidgetsData
     */
    public function testRenderWithCounter(SimpleWidget $widget, $template, $color)
    {
        $twig = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->onlyMethods(['render'])->getMock();
        $twig->expects($this->once())->method('render')->willReturnCallback(function ($name, $options) {
            return json_encode([$name, $options]);
        });

        $sut = new SimpleWidgetRenderer($twig);

        $data = uniqid(\get_class($widget));
        $widget->setData($data);
        $result = $sut->render($widget, ['color' => $color]);
        $result = json_decode($result, true);

        self::assertEquals($template, $result[0]);
        self::assertArrayHasKey('data', $result[1]);
        self::assertEquals($data, $result[1]['data']);
        self::assertArrayHasKey('title', $result[1]);
        self::assertArrayHasKey('options', $result[1]);
        self::assertIsArray($result[1]['options']);
        self::assertArrayHasKey('color', $result[1]['options']);
        self::assertEquals($color, $result[1]['options']['color']);
    }

    public function getSimpleWidgetsData()
    {
        return [
            [new SimpleWidget(), 'widget/widget-simplewidget.html.twig', 'yellow'],
            [new Counter(), 'widget/widget-counter.html.twig', 'asdfgh'],
            [new More(), 'widget/widget-more.html.twig', '#123456'],
        ];
    }
}
