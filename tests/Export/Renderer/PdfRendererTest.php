<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Export\Renderer\PDFRenderer;
use App\Project\ProjectStatisticService;
use App\Tests\Mocks\FileHelperFactory;
use App\Utils\HtmlToPdfConverter;
use App\Utils\MPdfConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Twig\Environment;

/**
 * @covers \App\Export\Base\PDFRenderer
 * @covers \App\Export\Base\RendererTrait
 * @covers \App\Export\Renderer\PDFRenderer
 * @group integration
 */
class PdfRendererTest extends AbstractRendererTest
{
    public function testDisposition()
    {
        $sut = new PDFRenderer(
            $this->createMock(Environment::class),
            $this->createMock(HtmlToPdfConverter::class),
            $this->createMock(ProjectStatisticService::class)
        );
        $this->assertEquals(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $sut->getDisposition());
        $sut->setDispositionInline(false);
        $this->assertEquals(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $sut->getDisposition());
        $sut->setDispositionInline(true);
        $this->assertEquals(ResponseHeaderBag::DISPOSITION_INLINE, $sut->getDisposition());
        $sut->setDispositionInline(false);
        $this->assertEquals(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $sut->getDisposition());
    }

    public function testConfiguration()
    {
        $sut = new PDFRenderer(
            $this->createMock(Environment::class),
            $this->createMock(HtmlToPdfConverter::class),
            $this->createMock(ProjectStatisticService::class)
        );

        $this->assertEquals('pdf', $sut->getId());
        $this->assertEquals('pdf', $sut->getTitle());
        $this->assertEquals('pdf', $sut->getIcon());
        $this->assertEquals([], $sut->getPdfOptions());

        $sut->setPdfOption('foo', 'bar');
        $sut->setPdfOption('bar1', 'foo1');
        $this->assertEquals(['foo' => 'bar', 'bar1' => 'foo1'], $sut->getPdfOptions());
    }

    public function testRender()
    {
        $kernel = self::bootKernel();
        /** @var Environment $twig */
        $twig = $kernel->getContainer()->get('twig');
        $stack = $kernel->getContainer()->get('request_stack');
        $cacheDir = $kernel->getContainer()->getParameter('kernel.cache_dir');
        $converter = new MPdfConverter((new FileHelperFactory($this))->create(), $cacheDir);
        $request = new Request();
        $request->setLocale('en');
        $stack->push($request);

        $sut = new PDFRenderer($twig, $converter, $this->createMock(ProjectStatisticService::class));

        $response = $this->render($sut);

        $prefix = date('Ymd');
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename=' . $prefix . '-Customer_Name-project_name.pdf', $response->headers->get('Content-Disposition'));

        $this->assertNotEmpty($response->getContent());
    }
}
