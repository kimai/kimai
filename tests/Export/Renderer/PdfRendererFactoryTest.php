<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Export\Base\PDFRenderer;
use App\Export\Renderer\PdfRendererFactory;
use App\Tests\Mocks\Export\PdfRendererFactoryMock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfRendererFactory::class)]
class PdfRendererFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $mock = new PdfRendererFactoryMock($this);
        $sut = $mock->create();

        $renderer = $sut->create('foo', 'bar.pdf.twig');

        self::assertInstanceOf(PDFRenderer::class, $renderer);
        self::assertEquals('foo', $renderer->getId());
        self::assertFalse($renderer->isInternal());
    }
}
