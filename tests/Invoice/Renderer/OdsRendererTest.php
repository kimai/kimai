<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Invoice\InvoiceModel;
use App\Invoice\Renderer\OdsRenderer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @covers \App\Invoice\Renderer\OdsRenderer
 * @covers \App\Invoice\Renderer\AbstractRenderer
 * @covers \App\Invoice\Renderer\AbstractSpreadsheetRenderer
 * @group integration
 */
class OdsRendererTest extends TestCase
{
    use RendererTestTrait;

    public function testSupports(): void
    {
        $sut = $this->getAbstractRenderer(OdsRenderer::class);

        self::assertFalse($sut->supports($this->getInvoiceDocument('invoice.html.twig')));
        self::assertFalse($sut->supports($this->getInvoiceDocument('service-date.pdf.twig')));
        self::assertFalse($sut->supports($this->getInvoiceDocument('timesheet.html.twig')));
        self::assertFalse($sut->supports($this->getInvoiceDocument('company.docx', true)));
        self::assertFalse($sut->supports($this->getInvoiceDocument('spreadsheet.xlsx', true)));
        self::assertTrue($sut->supports($this->getInvoiceDocument('open-spreadsheet.ods', true)));
    }

    public static function getTestModel()
    {
        yield [static fn (self $testCase) => $testCase->getInvoiceModel(), '1,947.99', 6, 5, 1, 2, 2];
        yield [static fn (self $testCase) => $testCase->getInvoiceModelOneEntry(), '293.27', 2, 1, 0, 1, 0];
    }

    /**
     * @dataProvider getTestModel
     */
    public function testRender(callable $invoiceModel, $expectedRate, $expectedRows, $expectedDescriptions, $expectedUser1, $expectedUser2, $expectedUser3): void
    {
        /** @var InvoiceModel $model */
        $model = $invoiceModel($this); // FIXME

        /** @var OdsRenderer $sut */
        $sut = $this->getAbstractRenderer(OdsRenderer::class);
        $model = $this->getInvoiceModel();
        $document = $this->getInvoiceDocument('open-spreadsheet.ods', true);
        /** @var BinaryFileResponse $response */
        $response = $sut->render($document, $model);

        $filename = $model->getInvoiceNumber() . '-customer_with_special_name.ods';
        $file = $response->getFile();
        self::assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        self::assertEquals('attachment; filename=' . $filename, $response->headers->get('Content-Disposition'));

        self::assertTrue(file_exists($file->getRealPath()));

        // TODO test spreadsheet content?
        /*
        $content = file_get_contents($file->getRealPath());
        self::assertNotContains('${', $content);
        self::assertStringContainsString(',"1,947.99" ', $content);
        self::assertEquals(6, substr_count($content, PHP_EOL));
        self::assertEquals(5, substr_count($content, 'activity description'));
        self::assertEquals(1, substr_count($content, ',"kevin",'));
        self::assertEquals(2, substr_count($content, ',"hello-world",'));
        self::assertEquals(2, substr_count($content, ',"foo-bar",'));
        */

        ob_start();
        $response->sendContent();
        $content2 = ob_get_clean();
        self::assertNotEmpty($content2);

        self::assertFalse(file_exists($file->getRealPath()));
    }
}
