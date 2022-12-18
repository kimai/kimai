<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Model\InvoiceDocument;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\InvoiceDocument
 */
class InvoiceDocumentTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $dir = realpath(__DIR__ . '/../../templates/invoice/renderer');
        $sut = new InvoiceDocument(new \SplFileInfo($dir . '/invoice.html.twig'));

        self::assertEquals('twig', $sut->getFileExtension());
        self::assertStringContainsString('templates/invoice/renderer/invoice.html.twig', $sut->getFilename());
        self::assertEquals('invoice', $sut->getId());
        self::assertEquals('invoice.html.twig', $sut->getName());
        self::assertIsInt($sut->getLastChange());
    }

    public function testThrowsOnDeletedFile(): void
    {
        $catchedException = false;

        $dir = realpath(__DIR__ . '/../../templates/invoice/renderer');
        if ($dir === false) {
            throw new \Exception('Directory for invoice renderer could not be accessed');
        }

        $file = tempnam($dir, 'invoice-renderer');

        if ($file !== false) {
            touch($file);
            $sut = new InvoiceDocument(new \SplFileInfo($file));
            unlink($file);

            try {
                // names are cached by SplFileInfo, so we need to trigger a function that does access the file
                $sut->getLastChange();
            } catch (\Exception $exception) {
                $catchedException = true;
            }
        }

        self::assertTrue($catchedException, 'Invoice document did not throw exception');
    }
}
