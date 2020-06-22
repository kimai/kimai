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

    protected static $testDocuments = [
        'spreadsheet.xsls',
        'open-spreadsheet.ods',
        'default.pdf.twig',
    ];

    protected static $defaultDocuments = [
        'company.docx',
        'default.html.twig',
        'default-pdf.pdf.twig',
        'freelancer.html.twig',
        'timesheet.html.twig',
        'text.txt.twig',
        'javascript.json.twig',
        'xml.xml.twig',
    ];

    public function testDirectories()
    {
        $sut = new InvoiceDocumentRepository([]);
        self::assertEmpty($sut->findAll());
        self::assertIsArray($sut->findAll());
        self::assertNull($sut->findByName('default'));

        try {
            $sut->getUploadDirectory();
            $this->fail('Expected exception was not raised');
        } catch (\Exception $ex) {
            $this->assertEquals('Unknown upload directory', $ex->getMessage());
        }

        $path = realpath(__DIR__ . '/../Invoice/templates/');
        $sut->addDirectory($path);
        $sut->addDirectory(InvoiceDocumentRepository::DEFAULT_DIRECTORY);
        $sut->addDirectory(__DIR__);
        self::assertEquals(__DIR__, $sut->getUploadDirectory());

        $sut->removeDirectory(__DIR__);

        self::assertCount(\count(self::$defaultDocuments), $sut->findBuiltIn());
        self::assertCount(\count(self::$testDocuments), $sut->findCustom());

        // template "default" exists twice, so its 10 instead of 11
        $all = [];
        foreach (self::$defaultDocuments as $document) {
            $all[] = substr($document, 0, strpos($document, '.'));
        }
        foreach (self::$testDocuments as $document) {
            $all[] = substr($document, 0, strpos($document, '.'));
        }
        $all = array_unique(array_values($all));
        self::assertCount(\count($all), $sut->findAll());

        self::assertEquals($path, $sut->getUploadDirectory());
    }

    public function testDefaultTemplatesExists()
    {
        $sut = new InvoiceDocumentRepository(self::$defaultDirectories);
        $all = $sut->findAll();
        $this->assertCount(\count(self::$defaultDocuments), $all);

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
