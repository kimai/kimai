<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Base;

use App\Export\Base\CsvRenderer;
use App\Export\Base\HtmlRenderer;
use App\Export\Base\PDFRenderer;
use App\Export\Base\XlsxRenderer;
use App\Export\ServiceExport;
use App\Repository\ExportTemplateRepository;
use App\Tests\Export\Renderer\AbstractRendererTestCase;
use App\Tests\Mocks\Export\CsvRendererFactoryMock;
use App\Tests\Mocks\Export\HtmlRendererFactoryMock;
use App\Tests\Mocks\Export\PdfRendererFactoryMock;
use App\Tests\Mocks\Export\XlsxRendererFactoryMock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[CoversClass(ServiceExport::class)]
#[CoversClass(CsvRenderer::class)]
#[CoversClass(XlsxRenderer::class)]
#[CoversClass(PDFRenderer::class)]
#[CoversClass(HtmlRenderer::class)]
#[Group('integration')]
class DefaultRendererTest extends AbstractRendererTestCase
{
    private function createServiceExport(): ServiceExport
    {
        $repository = $this->createMock(ExportTemplateRepository::class);
        $repository->expects($this->once())->method('findAll')->willReturn([]);
        $logger = $this->createMock(LoggerInterface::class);

        return new ServiceExport(
            $this->createMock(EventDispatcherInterface::class),
            (new HtmlRendererFactoryMock($this))->create(),
            (new PdfRendererFactoryMock($this))->create(),
            (new CsvRendererFactoryMock($this))->create(),
            (new XlsxRendererFactoryMock($this))->create(),
            $repository,
            $logger,
        );
    }

    public function testRenderDefaultTemplates(): void
    {
        $sut = $this->createServiceExport();

        $renderer = $sut->getRenderer();
        self::assertCount(4, $renderer);
        self::assertInstanceOf(CsvRenderer::class, $renderer[0]);
        self::assertInstanceOf(XlsxRenderer::class, $renderer[1]);
        self::assertInstanceOf(PDFRenderer::class, $renderer[2]);
        self::assertInstanceOf(HtmlRenderer::class, $renderer[3]);

        // make sure that the default templates do NOT violate the Twig SecurityPolicy
        $this->render($renderer[0]);
        $this->render($renderer[1]);
        $this->render($renderer[2]);
        $this->render($renderer[3]);
    }
}
