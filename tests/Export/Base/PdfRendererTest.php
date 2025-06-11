<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Base;

use App\Export\Base\PDFRenderer;
use App\Pdf\HtmlToPdfConverter;
use App\Project\ProjectStatisticService;
use App\Tests\Export\Renderer\AbstractRendererTestCase;
use Twig\Environment;

/**
 * @covers \App\Export\Base\PDFRenderer
 * @covers \App\Export\Base\RendererTrait
 * @covers \App\Pdf\PdfRendererTrait
 * @group integration
 */
class PdfRendererTest extends AbstractRendererTestCase
{
    protected function getAbstractRenderer(bool $exportDecimal = false): PDFRenderer
    {
        $twig = $this->createMock(Environment::class);
        $converter = $this->createMock(HtmlToPdfConverter::class);
        $projectStatisticService = $this->createMock(ProjectStatisticService::class);

        return new PDFRenderer($twig, $converter, $projectStatisticService);
    }

    public function testConfiguration(): void
    {
        $sut = $this->getAbstractRenderer();

        self::assertEquals('pdf', $sut->getId());
        self::assertEquals('pdf', $sut->getTitle());

        $sut->setTitle('foo-bar');
        self::assertEquals('foo-bar', $sut->getTitle());

        $sut->setId('bar-id');
        self::assertEquals('bar-id', $sut->getId());

        self::assertEquals([], $sut->getPdfOptions());
        $sut->setPdfOption('foo', 'bar');
        self::assertEquals(['foo' => 'bar'], $sut->getPdfOptions());
        $sut->setPdfOption('foo', 'bar2');
        self::assertEquals(['foo' => 'bar2'], $sut->getPdfOptions());
        $sut->setPdfOption('hello', 'world');
        self::assertEquals(['foo' => 'bar2', 'hello' => 'world'], $sut->getPdfOptions());
    }
}
