<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Repository\InvoiceDocumentRepository;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Repository\InvoiceDocumentRepository
 */
class InvoiceDocumentRepositoryTest extends TestCase
{
    /**
     * @var array<string>
     */
    private static array $defaultDirectories = [
        'templates/invoice/renderer'
    ];

    /**
     * @var array<string>
     */
    private static array $testDocuments = [
        'company.docx',
        'spreadsheet.xsls',
        'open-spreadsheet.ods',
        'default.pdf.twig',
    ];

    /**
     * @var array<string>
     */
    private static array $defaultDocuments = [
        'invoice.html.twig',
        'default.pdf.twig',
        'service-date.pdf.twig',
        'timesheet.html.twig',
    ];

    public function testDirectories(): void
    {
        $sut = new InvoiceDocumentRepository([]);
        self::assertEmpty($sut->findAll());
        self::assertIsArray($sut->findAll());
        self::assertNull($sut->findByName('default'));

        try {
            $sut->getUploadDirectory();
            $this->fail('Expected exception was not raised');
        } catch (\Exception $ex) {
            self::assertEquals('Unknown upload directory', $ex->getMessage());
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
        $all = array_unique($all);
        self::assertCount(\count($all), $sut->findAll());

        self::assertEquals($path, $sut->getUploadDirectory());
    }

    public function testDefaultTemplatesExists(): void
    {
        $sut = new InvoiceDocumentRepository(self::$defaultDirectories);
        $all = $sut->findAll();
        self::assertCount(\count(self::$defaultDocuments), $all);

        foreach ($all as $document) {
            self::assertTrue(\in_array($document->getName(), self::$defaultDocuments), 'Missing template: ' . $document->getName());
        }

        foreach (self::$defaultDocuments as $filename) {
            $filename = substr($filename, 0, strpos($filename, '.'));
            $actual = $sut->findByName($filename);
            self::assertNotNull($actual);
        }
    }
}
