<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Base;

use App\Export\Base\PDFRenderer;
use App\Export\Base\RendererTrait;
use App\Pdf\HtmlToPdfConverter;
use App\Pdf\PdfRendererTrait;
use App\Project\ProjectStatisticService;
use App\Tests\Export\Renderer\AbstractRendererTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

#[CoversClass(RendererTrait::class)]
#[CoversClass(PdfRendererTrait::class)]
#[CoversClass(PDFRenderer::class)]
#[Group('integration')]
class PdfRendererTest extends AbstractRendererTestCase
{
    protected function getAbstractRenderer(?Environment $environment = null): PDFRenderer
    {
        $converter = $this->createMock(HtmlToPdfConverter::class);
        $projectStatisticService = $this->createMock(ProjectStatisticService::class);

        return new PDFRenderer(
            $environment ?? $this->createMock(Environment::class),
            $converter,
            $projectStatisticService,
            'foo',
            'bar',
            'export/print.html.twig'
        );
    }

    public function testConfiguration(): void
    {
        $sut = $this->getAbstractRenderer();

        self::assertEquals('foo', $sut->getId());
        self::assertEquals('bar', $sut->getTitle());
        self::assertEquals([], $sut->getPdfOptions());
        $sut->setPdfOption('foo', 'bar');
        self::assertEquals(['foo' => 'bar'], $sut->getPdfOptions());
        $sut->setPdfOption('foo', 'bar2');
        self::assertEquals(['foo' => 'bar2'], $sut->getPdfOptions());
        $sut->setPdfOption('hello', 'world');
        self::assertEquals(['foo' => 'bar2', 'hello' => 'world'], $sut->getPdfOptions());
        self::assertFalse($sut->isInternal());
    }

    #[Group('legacy')]
    public function testLegacy(): void
    {
        $sut = $this->getAbstractRenderer();

        $sut->setTemplate('some'); // @phpstan-ignore method.deprecated
        $sut->setTitle('xxxxxx'); // @phpstan-ignore method.deprecated
        self::assertEquals('xxxxxx', $sut->getTitle());
    }

    public function testRender(): void
    {
        /** @var Environment $twig */
        $twig = $this->getContainer()->get(Environment::class);

        $sut = $this->getAbstractRenderer($twig);

        $response = $this->render($sut);
        self::assertInstanceOf(Response::class, $response);

        $prefix = date('Ymd');
        self::assertEquals('application/pdf', $response->headers->get('Content-Type'));
        self::assertEquals('attachment; filename=' . $prefix . '-Customer_Name-project_name.pdf', $response->headers->get('Content-Disposition'));

        $content = $response->getContent();
        self::assertIsString($content);
    }
}
