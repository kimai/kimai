<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Invoice\Renderer\AbstractTwigRenderer;
use App\Invoice\Renderer\TwigRenderer;
use App\Model\InvoiceDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

#[CoversClass(AbstractTwigRenderer::class)]
#[CoversClass(TwigRenderer::class)]
#[Group('integration')]
class TwigRendererTest extends KernelTestCase
{
    use RendererTestTrait;

    public function testSupports(): void
    {
        $loader = new FilesystemLoader();
        $env = new Environment($loader);
        $sut = new TwigRenderer($env);

        self::assertTrue($sut->supports($this->getInvoiceDocument('invoice.html.twig')));
        self::assertTrue($sut->supports($this->getInvoiceDocument('timesheet.html.twig')));
        self::assertFalse($sut->supports($this->getInvoiceDocument('service-date.pdf.twig')));
        self::assertFalse($sut->supports($this->getInvoiceDocument('company.docx', true)));
        self::assertFalse($sut->supports($this->getInvoiceDocument('spreadsheet.xlsx', true)));
        self::assertFalse($sut->supports($this->getInvoiceDocument('open-spreadsheet.ods', true)));
    }

    public function testRender(): void
    {
        $kernel = self::bootKernel();
        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');
        /** @var RequestStack $stack */
        $stack = self::getContainer()->get('request_stack');
        $request = new Request();
        $request->setLocale('en');
        $stack->push($request);

        /** @var FilesystemLoader $loader */
        $loader = $twig->getLoader();
        $loader->addPath($this->getInvoiceTemplatePath(), 'invoice');

        $sut = new TwigRenderer($twig);

        $model = $this->getInvoiceModel();
        $model->getTemplate()->setLanguage('de');

        $document = $this->getInvoiceDocument('timesheet.html.twig');
        $response = $sut->render($document, $model);

        $content = $response->getContent();

        $filename = $model->getInvoiceNumber() . '-customer_with_special_name';
        self::assertStringContainsString('<title>' . $filename . '</title>', $content);
        self::assertStringContainsString('<span contenteditable="true">a very *long* test invoice / template title with [ßpecial] chäracter</span>', $content);
        // 3 timesheets have a description and therefor do not render the activity
        // 2 timesheets have no description and the correct activity assigned
        self::assertEquals(2, substr_count($content, 'activity description'));
        self::assertStringContainsString(nl2br("foo\n" .
    "foo\r\n" .
    'foo' . PHP_EOL .
    "bar\n" .
    "bar\r\n" .
    'Hello'), $content);
    }

    public function testRenderAll(): void
    {
        $kernel = self::bootKernel();
        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');
        /** @var RequestStack $stack */
        $stack = self::getContainer()->get('request_stack');
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
                ->name('*.html.twig')
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
                ->name('*.html.twig')
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

        $allFiles = [];

        foreach ($files as $templates) {
            foreach ($templates as $filename) {
                $allFiles[] = $filename;
            }
        }

        self::assertGreaterThanOrEqual(2, \count($allFiles));

        $sut = new TwigRenderer($twig);
        $model = $this->getInvoiceModel();

        foreach ($allFiles as $filename) {
            $document = new InvoiceDocument(new \SplFileInfo($filename));

            $response = $sut->render($document, $model);
            self::assertEquals('text/html; charset=UTF-8', $response->headers->get('Content-Type'));
            self::assertNotEmpty($response->getContent());
        }
    }
}
