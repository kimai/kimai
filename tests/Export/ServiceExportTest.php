<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice;

use App\Export\Renderer\HtmlRenderer;
use App\Export\ServiceExport;
use PHPUnit\Framework\TestCase;

class ServiceExportTest extends TestCase
{
    public function testEmptyObject()
    {
        $sut = new ServiceExport();
        $this->assertEmpty($sut->getRenderer());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testUnknownRendererThrowsException()
    {
        $sut = new ServiceExport();
        $this->assertNull($sut->getRendererById('default'));
    }

    public function testAdd()
    {
        $sut = new ServiceExport();

        $sut->addRenderer(new HtmlRenderer(
            $this->getMockBuilder(\Twig_Environment::class)->disableOriginalConstructor()->getMock()
        ));

        $this->assertEquals(1, count($sut->getRenderer()));
    }
}
