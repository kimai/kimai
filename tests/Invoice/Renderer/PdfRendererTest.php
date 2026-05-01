<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Invoice\Renderer\AbstractTwigRenderer;
use App\Invoice\Renderer\PdfRenderer;
use App\Model\InvoiceDocument;
use App\Pdf\HtmlToPdfConverter;
use App\Pdf\MPdfConverter;
use App\Tests\Mocks\FileHelperFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\FilesystemLoader;

#[CoversClass(AbstractTwigRenderer::class)]
#[CoversClass(PdfRenderer::class)]
#[Group('integration')]
class PdfRendererTest extends KernelTestCase
{
    use RendererTestTrait;

    public function testSupports(): void
    {
        $env = new Environment(new ArrayLoader([]));
        $sut = new PdfRenderer($env, $this->createMock(HtmlToPdfConverter::class));
        self::assertTrue($sut->supports($this->getInvoiceDocument('default.pdf.twig', true)));
        self::assertTrue($sut->supports($this->getInvoiceDocument('service-date.pdf.twig')));
        self::assertFalse($sut->supports($this->getInvoiceDocument('timesheet.html.twig')));
        self::assertFalse($sut->supports($this->getInvoiceDocument('company.docx', true)));
        self::assertFalse($sut->supports($this->getInvoiceDocument('spreadsheet.xlsx', true)));
        self::assertFalse($sut->supports($this->getInvoiceDocument('open-spreadsheet.ods', true)));
    }

    public function testRenderAttachment(): void
    {
        $kernel = self::bootKernel();
        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');
        /** @var RequestStack $stack */
        $stack = self::getContainer()->get('request_stack');
        $cacheDir = $kernel->getContainer()->getParameter('kernel.cache_dir');

        $request = new Request();
        $request->setLocale('en');
        $stack->push($request);

        /** @var FilesystemLoader $loader */
        $loader = $twig->getLoader();
        $loader->addPath($this->getInvoiceTemplatePath(), 'invoice');

        $sut = new PdfRenderer($twig, new MPdfConverter((new FileHelperFactory($this))->create(), $cacheDir));
        $model = $this->getInvoiceModel();
        $document = $this->getInvoiceDocument('default.pdf.twig', true);

        $response = $sut->render($document, $model);
        self::assertEquals('application/pdf', $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment; filename', $response->headers->get('Content-Disposition'));
        self::assertNotEmpty($response->getContent());
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

        $request = new Request();
        $request->setLocale('en');
        $stack->push($request);

        /** @var FilesystemLoader $loader */
        $loader = $twig->getLoader();
        $loader->addPath(__DIR__ . '/../templates/', 'invoice');

        $sut = new PdfRenderer($twig, new MPdfConverter((new FileHelperFactory($this))->create(), $cacheDir));
        $model = $this->getInvoiceModel();
        $document = $this->getInvoiceDocument('default.pdf.twig', true);

        $sut->setDispositionInline(true);
        $response = $sut->render($document, $model);
        self::assertStringContainsString('inline; filename', $response->headers->get('Content-Disposition'));
        self::assertNotEmpty($response->getContent());
    }

    public function testRenderAll(): void
    {
        $kernel = self::bootKernel();
        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');
        /** @var RequestStack $stack */
        $stack = self::getContainer()->get('request_stack');
        /** @var string $cacheDir */
        $cacheDir = $kernel->getContainer()->getParameter('kernel.cache_dir');

        $request = new Request();
        $request->setLocale('en');
        $stack->push($request);

        /** @var FilesystemLoader $loader */
        $loader = $twig->getLoader();

        $files = [];

        $dirs = [
            realpath($this->getInvoiceTemplatePath()),
            realpath(__DIR__ . '/../templates/'),
            realpath(__DIR__ . '/../../../var/invoices/'),
        ];

        foreach ($dirs as $dir) {
            if ($dir === false || !is_dir($dir)) {
                continue;
            }

            $finder = new Finder();
            $finder
                ->in($dir)
                ->name('*.pdf.twig')
                ->sortByName()
                ->files();

            foreach ($finder->getIterator() as $splFile) {
                $filename = $splFile->getRealPath();
                if ($filename === false) {
                    continue;
                }
                $dir = \dirname($filename) . '/';
                if (!\array_key_exists($dir, $files)) {
                    $loader->addPath($dir . '/', 'invoice');
                }
                $files[$dir][] = $filename;
            }
        }

        // search for custom templates, that shall not be shipped
        $dirs = [
            realpath(__DIR__ . '/../../../var/templates/'),
        ];

        foreach ($dirs as $dir) {
            if ($dir === false || !is_dir($dir)) {
                continue;
            }

            $finder = new Finder();
            $finder
                ->in($dir)
                ->name('*.pdf.twig')
                ->path('invoice-tpl/')
                ->sortByName()
                ->files();

            foreach ($finder->getIterator() as $splFile) {
                $filename = $splFile->getRealPath();
                if ($filename === false) {
                    continue;
                }
                $dir = \dirname($filename) . '/';
                if (!\array_key_exists($dir, $files)) {
                    $loader->addPath($dir . '/', 'invoice');
                }
                $files[$dir][] = $filename;
            }
        }

        $sut = new PdfRenderer($twig, new MPdfConverter((new FileHelperFactory($this))->create(), $cacheDir));
        $model = $this->getInvoiceModel();

        $allFiles = [];

        foreach ($files as $templates) {
            foreach ($templates as $filename) {
                $allFiles[] = $filename;
            }
        }

        self::assertGreaterThanOrEqual(3, \count($allFiles));

        foreach ($allFiles as $filename) {
            $document = new InvoiceDocument(new \SplFileInfo($filename));

            $response = $sut->render($document, $model);
            self::assertEquals('application/pdf', $response->headers->get('Content-Type'));
            self::assertStringContainsString('attachment; filename', $response->headers->get('Content-Disposition'));
        }
    }
}
