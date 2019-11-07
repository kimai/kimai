<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Renderer;

use App\Widget\Renderer\CompoundRowRenderer;
use App\Widget\Type\CompoundChart;
use App\Widget\Type\CompoundRow;
use App\Widget\Type\Counter;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

/**
 * @covers \App\Widget\Renderer\CompoundRowRenderer
 * @covers \App\Widget\Renderer\AbstractTwigRenderer
 */
class CompoundRowRendererTest extends TestCase
{
    public function testSupports()
    {
        $twig = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock();
        $sut = new CompoundRowRenderer($twig);
        self::assertTrue($sut->supports(new CompoundRow()));
        self::assertFalse($sut->supports(new CompoundChart()));
    }

    public function testRenderWithCounter()
    {
        $twig = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->onlyMethods(['render'])->getMock();
        $twig->expects($this->once())->method('render')->willReturnCallback(function ($name, $options) {
            return json_encode([$name, $options]);
        });

        $sut = new CompoundRowRenderer($twig);
        $row = new CompoundRow();
        $row->setTitle('foo-bar');
        $row->addWidget(new Counter());

        $result = $sut->render($row);
        $result = json_decode($result, true);
        self::assertEquals('widget/section-simple.html.twig', $result[0]);
        self::assertArrayHasKey('title', $result[1]);
        self::assertEquals('foo-bar', $result[1]['title']);
        self::assertArrayHasKey('widgets', $result[1]);
        self::assertIsArray($result[1]['widgets']);
        self::assertCount(1, $result[1]['widgets']);
    }
}
