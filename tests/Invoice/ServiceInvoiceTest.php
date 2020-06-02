<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice;

use App\Entity\Invoice;
use App\Entity\InvoiceDocument;
use App\Invoice\Calculator\DefaultCalculator;
use App\Invoice\NumberGenerator\DateNumberGenerator;
use App\Invoice\Renderer\TwigRenderer;
use App\Invoice\ServiceInvoice;
use App\Repository\InvoiceDocumentRepository;
use App\Repository\InvoiceRepository;
use App\Tests\Mocks\Security\UserDateTimeFactoryFactory;
use App\Utils\FileHelper;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

/**
 * @covers \App\Invoice\ServiceInvoice
 */
class ServiceInvoiceTest extends TestCase
{
    private function getSut(array $paths): ServiceInvoice
    {
        $repo = new InvoiceDocumentRepository($paths);
        $invoiceRepo = $this->createMock(InvoiceRepository::class);
        $userDateTime = (new UserDateTimeFactoryFactory($this))->create();

        return new ServiceInvoice($repo, new FileHelper(realpath(__DIR__ . '/../../var/data/')), $invoiceRepo, $userDateTime, new DebugFormatter());
    }

    public function testInvalidExceptionOnChangeState()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown invoice status');
        $sut = $this->getSut([]);
        $sut->changeInvoiceStatus(new Invoice(), 'foo');
    }

    public function testEmptyObject()
    {
        $sut = $this->getSut([]);

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
        $sut = $this->getSut(['templates/invoice/renderer/']);

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
        $sut = $this->getSut([]);

        $sut->addCalculator(new DefaultCalculator());
        $sut->addNumberGenerator(new DateNumberGenerator());
        $sut->addRenderer(
            new TwigRenderer(
                $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock()
            )
        );

        $this->assertEquals(1, \count($sut->getCalculator()));
        $this->assertInstanceOf(DefaultCalculator::class, $sut->getCalculatorByName('default'));

        $this->assertEquals(1, \count($sut->getNumberGenerator()));
        $this->assertInstanceOf(DateNumberGenerator::class, $sut->getNumberGeneratorByName('date'));

        $this->assertEquals(1, \count($sut->getRenderer()));
    }
}
