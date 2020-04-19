<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Entity\InvoiceDocument;
use App\Repository\InvoiceDocumentRepository;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Repository\InvoiceDocumentRepository
 */
class InvoiceDocumentRepositoryTest extends TestCase
{
    protected static $defaultDirectories = [
        'templates/invoice/renderer'
    ];

    protected static $defaultDocuments = [
        'company.docx',
        'default.html.twig',
        'freelancer.html.twig',
        'timesheet.html.twig',
        'text.txt.twig',
        'javascript.json.twig',
        'xml.xml.twig',
    ];

    public function testWithEmptyDirectory()
    {
        $sut = new InvoiceDocumentRepository([]);
        $this->assertEmpty($sut->findAll());
        $this->assertIsArray($sut->findAll());
        $this->assertNull($sut->findByName('default'));
    }

    public function testDefaultTemplatesExists()
    {
        $sut = new InvoiceDocumentRepository(self::$defaultDirectories);
        $all = $sut->findAll();
        $this->assertEquals(\count(self::$defaultDocuments), \count($all));

        foreach ($all as $document) {
            $this->assertTrue(\in_array($document->getName(), self::$defaultDocuments));
        }

        foreach (self::$defaultDocuments as $filename) {
            $filename = substr($filename, 0, strpos($filename, '.'));
            $actual = $sut->findByName($filename);
            $this->assertNotNull($actual);
            $this->assertInstanceOf(InvoiceDocument::class, $actual);
        }
    }
}
