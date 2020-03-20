<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Invoice\Renderer\TwigRenderer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @covers \App\Invoice\Renderer\TwigRenderer
 * @group integration
 */
class TwigRendererTest extends KernelTestCase
{
    use RendererTestTrait;

    public function testSupports()
    {
        $loader = new FilesystemLoader();
        $env = new Environment($loader);
        $sut = new TwigRenderer($env);

        $this->assertTrue($sut->supports($this->getInvoiceDocument('default.html.twig')));
        $this->assertTrue($sut->supports($this->getInvoiceDocument('freelancer.html.twig')));
        $this->assertTrue($sut->supports($this->getInvoiceDocument('timesheet.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('foo.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('company.docx')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('export.csv')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('spreadsheet.xlsx')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('open-spreadsheet.ods')));
    }

    public function testRender()
    {
        $kernel = self::bootKernel();
        /** @var Environment $twig */
        $twig = $kernel->getContainer()->get('twig');
        $stack = $kernel->getContainer()->get('request_stack');
        $request = new Request();
        $request->setLocale('en');
        $stack->push($request);

        /** @var FilesystemLoader $loader */
        $loader = $twig->getLoader();
        $loader->addPath($this->getInvoiceTemplatePath(), 'invoice');

        $sut = new TwigRenderer($twig);

        $model = $this->getInvoiceModel();

        $document = $this->getInvoiceDocument('timesheet.html.twig');
        $response = $sut->render($document, $model);

        $content = $response->getContent();

        $filename = $model->getInvoiceNumber() . '-customer_with_special_name';
        $this->assertStringContainsString('<title>' . $filename . '</title>', $content);
        $this->assertStringContainsString('<h2 class="page-header">
           <span contenteditable="true">a very *long* test invoice / template title with [special] character</span>
        </h2>', $content);
        $this->assertEquals(4, substr_count($content, 'activity description'));
    }
}
