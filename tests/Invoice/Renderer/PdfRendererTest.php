<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Invoice\Renderer\PdfRenderer;
use App\Tests\Mocks\FileHelperFactory;
use App\Utils\MPdfConverter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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

    public function testDisposition()
    {
        $env = new Environment(new ArrayLoader([]));
        $sut = new PdfRenderer($env, $this->createMock(MPdfConverter::class));
        $this->assertEquals(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $sut->getDisposition());
        $sut->setDispositionInline(false);
        $this->assertEquals(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $sut->getDisposition());
        $sut->setDispositionInline(true);
        $this->assertEquals(ResponseHeaderBag::DISPOSITION_INLINE, $sut->getDisposition());
        $sut->setDispositionInline(false);
        $this->assertEquals(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $sut->getDisposition());
    }

    public function testSupports()
    {
        $env = new Environment(new ArrayLoader([]));
        $sut = new PdfRenderer($env, $this->createMock(MPdfConverter::class));
        $this->assertTrue($sut->supports($this->getInvoiceDocument('default.pdf.twig', true)));
        $this->assertTrue($sut->supports($this->getInvoiceDocument('freelancer.pdf.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('timesheet.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('company.docx')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('spreadsheet.xlsx', true)));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('open-spreadsheet.ods', true)));
    }

    public function testRender()
    {
        $kernel = self::bootKernel();
        /** @var Environment $twig */
        $twig = $kernel->getContainer()->get('twig');
        $stack = $kernel->getContainer()->get('request_stack');
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
    }
}
