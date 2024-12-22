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
use App\Pdf\HtmlToPdfConverter;
use App\Project\ProjectStatisticService;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

/**
 * @covers \App\Export\Renderer\PdfRendererFactory
 */
class PdfRendererFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $sut = new PdfRendererFactory(
            $this->createMock(Environment::class),
            $this->createMock(HtmlToPdfConverter::class),
            $this->createMock(ProjectStatisticService::class)
        );

        $renderer = $sut->create('foo', 'bar.pdf.twig');

        self::assertInstanceOf(PDFRenderer::class, $renderer);
        self::assertEquals('foo', $renderer->getId());
    }
}
