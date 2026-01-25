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
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

#[CoversClass(ServiceExport::class)]
#[CoversClass(CsvRenderer::class)]
#[CoversClass(XlsxRenderer::class)]
#[CoversClass(PDFRenderer::class)]
#[CoversClass(HtmlRenderer::class)]
#[Group('integration')]
class DefaultRendererTest extends AbstractRendererTestCase
{
    private function createServiceExport(?Environment $environment = null): ServiceExport
    {
        $repository = $this->createMock(ExportTemplateRepository::class);
        $repository->expects($this->once())->method('findAll')->willReturn([]);
        $logger = $this->createMock(LoggerInterface::class);

        return new ServiceExport(
            $this->createMock(EventDispatcherInterface::class),
            (new HtmlRendererFactoryMock($this))->create($environment),
            (new PdfRendererFactoryMock($this))->create($environment),
            (new CsvRendererFactoryMock($this))->create(),
            (new XlsxRendererFactoryMock($this))->create(),
            $repository,
            $logger,
        );
    }

    public function testRenderDefaultTemplates(): void
    {
        /** @var Environment $twig */
        $twig = $this->getContainer()->get(Environment::class);

        $sut = $this->createServiceExport($twig);

        $renderer = $sut->getRenderer();
        self::assertCount(4, $renderer);
        self::assertInstanceOf(CsvRenderer::class, $renderer[0]);
        self::assertInstanceOf(XlsxRenderer::class, $renderer[1]);
        self::assertInstanceOf(PDFRenderer::class, $renderer[2]);
        self::assertInstanceOf(HtmlRenderer::class, $renderer[3]);

        // make sure that the default templates do NOT violate the Twig SecurityPolicy
        $response = $this->render($renderer[0]);
        self::assertEquals('text/csv', $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment; filename', $response->headers->get('Content-Disposition') ?? '');

        $response = $this->render($renderer[1]);
        self::assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type') ?? '');
        self::assertStringContainsString('attachment; filename', $response->headers->get('Content-Disposition') ?? '');

        $response = $this->render($renderer[2]);
        self::assertEquals('application/pdf', $response->headers->get('Content-Type') ?? '');
        self::assertStringContainsString('attachment; filename', $response->headers->get('Content-Disposition') ?? '');

        $response = $this->render($renderer[3]);
        self::assertEquals('text/html', $response->headers->get('Content-Type') ?? '');
        // HTML is attached to the body and twig is a mock in this setupo, so we just receive an empty string
    }

    public function testRenderCustomTemplates(): void
    {
        $searchDir = __DIR__ . '/../../../var/templates';
        if (!is_dir($searchDir)) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $finder = new Finder();
        $finder
            ->in($searchDir)
            ->name('*.twig')
            ->path('export-tpl/')
            ->files()
        ;

        /** @var Environment $twig */
        $twig = $this->getContainer()->get(Environment::class);

        /** @var FilesystemLoader $loader */
        $loader = $twig->getLoader();

        $files = [];
        $dirs = [];
        foreach ($finder->getIterator() as $filename => $splFile) {
            $files[] = $splFile->getRealPath();
            $dir = \dirname($splFile->getRealPath());
            if (!\array_key_exists($dir, $dirs)) {
                $dirs[$dir] = $dir;
                $loader->addPath($dir . '/', 'export');
            }
        }
        $dirs = array_keys($dirs);

        if (\count($dirs) === 0) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $sut = $this->createServiceExport($twig);
        foreach ($dirs as $dir) {
            $sut->addDirectory($dir);
        }

        $renderers = $sut->getRenderer();
        self::assertCount(4 + \count($files), $renderers);
        foreach ($renderers as $renderer) {
            $response = $this->render($renderer);
            self::assertInstanceOf(Response::class, $response);
        }
    }
}
