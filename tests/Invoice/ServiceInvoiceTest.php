<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice;

use App\Entity\InvoiceDocument;
use App\Invoice\Calculator\DefaultCalculator;
use App\Invoice\NumberGenerator\DateNumberGenerator;
use App\Invoice\Renderer\TwigRenderer;
use App\Invoice\ServiceInvoice;
use App\Repository\InvoiceDocumentRepository;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

/**
 * @covers \App\Invoice\ServiceInvoice
 */
class ServiceInvoiceTest extends TestCase
{
    public function testEmptyObject()
    {
        $repo = new InvoiceDocumentRepository([]);
        $sut = new ServiceInvoice($repo);

        $this->assertEmpty($sut->getCalculator());
        $this->assertIsArray($sut->getCalculator());
        $this->assertEmpty($sut->getRenderer());
        $this->assertIsArray($sut->getRenderer());
        $this->assertEmpty($sut->getNumberGenerator());
        $this->assertIsArray($sut->getNumberGenerator());
        $this->assertEmpty($sut->getDocuments());
        $this->assertIsArray($sut->getDocuments());

        $this->assertNull($sut->getCalculatorByName('default'));
        $this->assertNull($sut->getDocumentByName('default'));
        $this->assertNull($sut->getNumberGeneratorByName('default'));
    }

    public function testWithDocumentDirectory()
    {
        $repo = new InvoiceDocumentRepository(['templates/invoice/renderer/']);
        $sut = new ServiceInvoice($repo);

        $actual = $sut->getDocuments();
        $this->assertNotEmpty($actual);
        foreach ($actual as $document) {
            $this->assertInstanceOf(InvoiceDocument::class, $document);
        }

        $actual = $sut->getDocumentByName('default');
        $this->assertInstanceOf(InvoiceDocument::class, $actual);
    }

    public function testAdd()
    {
        $repo = new InvoiceDocumentRepository([]);
        $sut = new ServiceInvoice($repo);

        $sut->addCalculator(new DefaultCalculator());
        $sut->addNumberGenerator(new DateNumberGenerator());
        $sut->addRenderer(
            new TwigRenderer(
                $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock()
            )
        );

        $this->assertEquals(1, count($sut->getCalculator()));
        $this->assertInstanceOf(DefaultCalculator::class, $sut->getCalculatorByName('default'));

        $this->assertEquals(1, count($sut->getNumberGenerator()));
        $this->assertInstanceOf(DateNumberGenerator::class, $sut->getNumberGeneratorByName('default'));

        $this->assertEquals(1, count($sut->getRenderer()));
    }
}
