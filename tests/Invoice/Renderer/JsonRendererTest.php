<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Invoice\Renderer\JsonRenderer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @covers \App\Invoice\Renderer\JsonRenderer
 * @group integration
 */
class JsonRendererTest extends KernelTestCase
{
    use RendererTestTrait;

    public function testSupports()
    {
        $loader = new FilesystemLoader();
        $env = new Environment($loader);
        $sut = new JsonRenderer($env);

        $this->assertFalse($sut->supports($this->getInvoiceDocument('default.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('freelancer.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('timesheet.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('foo.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('company.docx')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('export.csv')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('spreadsheet.xlsx')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('open-spreadsheet.ods')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('text.txt.twig')));
        $this->assertTrue($sut->supports($this->getInvoiceDocument('javascript.json.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('xml.xml.twig')));
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

        $sut = new JsonRenderer($twig);

        $model = $this->getInvoiceModel();

        $document = $this->getInvoiceDocument('javascript.json.twig');
        $response = $sut->render($document, $model);

        self::assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $json = json_decode($content, true);

        $expected = $model->toArray();
        $expected['items'] = [];
        foreach ($model->getCalculator()->getEntries() as $entry) {
            $expected['items'][] = $model->itemToArray($entry);
        }
        self::assertEquals($expected, $json);
    }
}
