<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Pdf;

use App\Pdf\PdfContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContext::class)]
class PdfContextTest extends TestCase
{
    public function testEmptyObject(): void
    {
        $sut = new PdfContext();

        self::assertIsArray($sut->getOptions());
        self::assertEmpty($sut->getOptions());
        self::assertNull($sut->getOption('unknown'));
    }

    public function testAllowedKeysRoundTrip(): void
    {
        $sut = new PdfContext();

        $sut->setOption('margin_top', '12');
        $sut->setOption('format', 'A4-P');
        $sut->setOption('PDFA', true);
        $sut->setOption('fonts', ['custom' => ['R' => 'custom.ttf']]);

        self::assertEquals('12', $sut->getOption('margin_top'));
        self::assertEquals('A4-P', $sut->getOption('format'));
        self::assertTrue($sut->getOption('PDFA'));
        self::assertEquals(['custom' => ['R' => 'custom.ttf']], $sut->getOption('fonts'));
        self::assertCount(4, $sut->getOptions());
    }

    /**
     * Templates running in the Twig sandbox must not be able to push the
     * file-disclosure sinks (`associated_files` with `path`, `additional_xmp_rdf`)
     * or arbitrary mPDF config keys through PdfContext.
     */
    public function testForbiddenAndUnknownKeysAreDropped(): void
    {
        $sut = new PdfContext();

        $sut->setOption('associated_files', [['path' => '/etc/passwd']]);
        $sut->setOption('additional_xmp_rdf', '<rdf:Description/>');
        $sut->setOption('tempDir', '/tmp');
        $sut->setOption('unknown', 'foo');

        self::assertNull($sut->getOption('associated_files'));
        self::assertNull($sut->getOption('additional_xmp_rdf'));
        self::assertNull($sut->getOption('tempDir'));
        self::assertNull($sut->getOption('unknown'));
        self::assertEmpty($sut->getOptions());
    }
}
