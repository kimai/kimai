<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Invoice\Renderer\TextRenderer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @covers \App\Invoice\Renderer\TextRenderer
 * @group integration
 */
class TextRendererTest extends KernelTestCase
{
    use RendererTestTrait;

    public function testSupports()
    {
        $loader = new FilesystemLoader();
        $env = new Environment($loader);
        $sut = new TextRenderer($env);

        $this->assertFalse($sut->supports($this->getInvoiceDocument('default.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('freelancer.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('timesheet.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('foo.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('company.docx')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('export.csv')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('spreadsheet.xlsx')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('open-spreadsheet.ods')));
        $this->assertTrue($sut->supports($this->getInvoiceDocument('text.txt.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('javascript.json.twig')));
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

        $sut = new TextRenderer($twig);

        $model = $this->getInvoiceModel();

        $document = $this->getInvoiceDocument('text.txt.twig');
        $response = $sut->render($document, $model);

        self::assertEquals('text/plain', $response->headers->get('Content-Type'));

        $content = $response->getContent();

        foreach ($model->toArray() as $key => $value) {
            if (null === $value || '' === $value) {
                self::assertStringContainsString(sprintf("%s\n", $key), $content);
            } else {
                self::assertStringContainsString(sprintf("%s\n	%s", $key, explode("\n", $value)[0]), $content);
            }
        }

        foreach ($model->getCalculator()->getEntries() as $entry) {
            foreach ($model->itemToArray($entry) as $key => $value) {
                if (null === $value || '' === $value) {
                    self::assertStringContainsString(sprintf("%s\n", $key), $content);
                } else {
                    self::assertStringContainsString(sprintf("%s\n	%s", $key, explode("\n", $value)[0]), $content);
                }
            }
        }
        self::assertEquals(\count($model->getCalculator()->getEntries()), substr_count($content, PHP_EOL . '---' . PHP_EOL));
    }
}
