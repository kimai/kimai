<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Invoice\InvoiceModel;
use App\Invoice\Renderer\CsvRenderer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @covers \App\Invoice\Renderer\CsvRenderer
 * @covers \App\Invoice\Renderer\AbstractRenderer
 * @covers \App\Invoice\Renderer\AbstractSpreadsheetRenderer
 * @covers \App\Invoice\Renderer\AdvancedValueBinder
 * @group integration
 */
class CsvRendererTest extends TestCase
{
    use RendererTestTrait;

    public function testSupports()
    {
        $sut = $this->getAbstractRenderer(CsvRenderer::class);

        $this->assertFalse($sut->supports($this->getInvoiceDocument('default.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('freelancer.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('timesheet.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('foo.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('company.docx')));
        $this->assertTrue($sut->supports($this->getInvoiceDocument('export.csv', true)));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('spreadsheet.xlsx')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('open-spreadsheet.ods')));
    }

    public function getTestModel()
    {
        yield [$this->getInvoiceModel(), '€1,947.99', 6, 4, 1, 2, 2];
        yield [$this->getInvoiceModelOneEntry(), '$293.27', 2, 1, 0, 1, 0];
    }

    /**
     * @dataProvider getTestModel
     */
    public function testRender(InvoiceModel $model, $expectedRate, $expectedRows, $expectedDescriptions, $expectedUser1, $expectedUser2, $expectedUser3)
    {
        /** @var CsvRenderer $sut */
        $sut = $this->getAbstractRenderer(CsvRenderer::class);
        $document = $this->getInvoiceDocument('export.csv', true);
        /** @var BinaryFileResponse $response */
        $response = $sut->render($document, $model);

        $file = $response->getFile();
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));
        $filename = $model->getInvoiceNumber() . '-customer_with_special_name.csv';
        $this->assertEquals('attachment; filename=' . $filename, $response->headers->get('Content-Disposition'));

        $this->assertTrue(file_exists($file->getRealPath()));
        $content = file_get_contents($file->getRealPath());

        $this->assertStringNotContainsString('${', $content);
        $this->assertStringContainsString(',"' . $expectedRate . '"', $content);
        $this->assertEquals($expectedRows, substr_count($content, PHP_EOL));
        $this->assertEquals($expectedDescriptions, substr_count($content, 'activity description'));
        $this->assertEquals($expectedUser1, substr_count($content, ',"kevin",'));
        $this->assertEquals($expectedUser3, substr_count($content, ',"hello-world",'));
        $this->assertEquals($expectedUser2, substr_count($content, ',"foo-bar",'));

        ob_start();
        $response->sendContent();
        $content2 = ob_get_clean();

        $this->assertEquals($content, $content2);
        $this->assertFalse(file_exists($file->getRealPath()));
    }
}
