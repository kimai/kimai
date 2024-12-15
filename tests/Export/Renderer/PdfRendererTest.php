<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Export\Renderer\PDFRenderer;
use App\Pdf\HtmlToPdfConverter;
use App\Pdf\MPdfConverter;
use App\Project\ProjectStatisticService;
use App\Tests\Mocks\FileHelperFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

/**
 * @covers \App\Export\Base\PDFRenderer
 * @covers \App\Export\Base\RendererTrait
 * @covers \App\Export\Renderer\PDFRenderer
 * @group integration
 */
class PdfRendererTest extends AbstractRendererTest
{
    public function testConfiguration(): void
    {
        $sut = new PDFRenderer(
            $this->createMock(Environment::class),
            $this->createMock(HtmlToPdfConverter::class),
            $this->createMock(ProjectStatisticService::class)
        );

        $this->assertEquals('pdf', $sut->getId());
        $this->assertEquals('pdf', $sut->getTitle());
        $this->assertEquals([], $sut->getPdfOptions());

        $sut->setPdfOption('foo', 'bar');
        $sut->setPdfOption('bar1', 'foo1');
        $this->assertEquals(['foo' => 'bar', 'bar1' => 'foo1'], $sut->getPdfOptions());
    }

    public function testRenderAttachment(): void
    {
        $kernel = self::bootKernel();
        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');
        /** @var RequestStack $stack */
        $stack = self::getContainer()->get('request_stack');
        /** @var string $cacheDir */
        $cacheDir = $kernel->getContainer()->getParameter('kernel.cache_dir');
        $converter = new MPdfConverter((new FileHelperFactory($this))->create(), $cacheDir);
        $request = new Request();
        $request->setLocale('en');
        $stack->push($request);

        $sut = new PDFRenderer($twig, $converter, $this->createMock(ProjectStatisticService::class));

        $prefix = date('Ymd');

        $response = $this->render($sut);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename=' . $prefix . '-Customer_Name-project_name.pdf', $response->headers->get('Content-Disposition'));
        $this->assertNotEmpty($response->getContent());
    }

    public function testRenderInline(): void
    {
        $kernel = self::bootKernel();
        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');
        /** @var RequestStack $stack */
        $stack = self::getContainer()->get('request_stack');
        /** @var string $cacheDir */
        $cacheDir = $kernel->getContainer()->getParameter('kernel.cache_dir');
        $converter = new MPdfConverter((new FileHelperFactory($this))->create(), $cacheDir);
        $request = new Request();
        $request->setLocale('en');
        $stack->push($request);

        $sut = new PDFRenderer($twig, $converter, $this->createMock(ProjectStatisticService::class));

        $prefix = date('Ymd');

        $sut->setDispositionInline(true);
        $response = $this->render($sut);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertEquals('inline; filename=' . $prefix . '-Customer_Name-project_name.pdf', $response->headers->get('Content-Disposition'));
        $this->assertNotEmpty($response->getContent());
    }
}
