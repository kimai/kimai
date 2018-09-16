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

class ServiceInvoiceTest extends TestCase
{
    public function testEmptyObject()
    {
        $repo = new InvoiceDocumentRepository([]);
        $sut = new ServiceInvoice($repo);

        $this->assertEmpty($sut->getCalculator());
        $this->assertInternalType('array', $sut->getCalculator());
        $this->assertEmpty($sut->getRenderer());
        $this->assertInternalType('array', $sut->getRenderer());
        $this->assertEmpty($sut->getNumberGenerator());
        $this->assertInternalType('array', $sut->getNumberGenerator());
        $this->assertEmpty($sut->getDocuments());
        $this->assertInternalType('array', $sut->getDocuments());

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
        foreach($actual as $document) {
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
                $this->getMockBuilder(\Twig_Environment::class)->disableOriginalConstructor()->getMock()
            )
        );

        $this->assertEquals(1, count($sut->getCalculator()));
        $this->assertInstanceOf(DefaultCalculator::class, $sut->getCalculatorByName('default'));

        $this->assertEquals(1, count($sut->getNumberGenerator()));
        $this->assertInstanceOf(DateNumberGenerator::class, $sut->getNumberGeneratorByName('default'));

        $this->assertEquals(1, count($sut->getRenderer()));
    }
}
