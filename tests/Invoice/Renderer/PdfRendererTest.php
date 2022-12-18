<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Invoice\Renderer\PdfRenderer;
use App\Pdf\HtmlToPdfConverter;
use App\Pdf\MPdfConverter;
use App\Tests\Mocks\FileHelperFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\FilesystemLoader;

/**
 * @covers \App\Invoice\Renderer\AbstractTwigRenderer
 * @covers \App\Invoice\Renderer\PdfRenderer
 * @group integration
 */
class PdfRendererTest extends KernelTestCase
{
    use RendererTestTrait;

    public function testSupports()
    {
        $env = new Environment(new ArrayLoader([]));
        $sut = new PdfRenderer($env, $this->createMock(HtmlToPdfConverter::class));
        $this->assertTrue($sut->supports($this->getInvoiceDocument('default.pdf.twig', true)));
        $this->assertTrue($sut->supports($this->getInvoiceDocument('service-date.pdf.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('timesheet.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('company.docx', true)));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('spreadsheet.xlsx', true)));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('open-spreadsheet.ods', true)));
    }

    public function testRender()
    {
        $kernel = self::bootKernel();
        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');
        $stack = self::getContainer()->get('request_stack');
        $cacheDir = $kernel->getContainer()->getParameter('kernel.cache_dir');

        $request = new Request();
        $request->setLocale('en');
        $stack->push($request);

        /** @var FilesystemLoader $loader */
        $loader = $twig->getLoader();
        $loader->addPath(__DIR__ . '/../templates/', 'invoice');

        $sut = new PdfRenderer($twig, new MPdfConverter((new FileHelperFactory($this))->create(), $cacheDir));
        $model = $this->getInvoiceModel();
        $document = $this->getInvoiceDocument('default.pdf.twig', true);

        $response = $sut->render($document, $model);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename', $response->headers->get('Content-Disposition'));
        $this->assertNotEmpty($response->getContent());

        $sut->setDispositionInline(true);
        $response = $sut->render($document, $model);
        $this->assertStringContainsString('inline; filename', $response->headers->get('Content-Disposition'));
    }
}
