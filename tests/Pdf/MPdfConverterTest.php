<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Pdf;

use App\Pdf\MPdfConverter;
use App\Tests\Mocks\FileHelperFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(MPdfConverter::class)]
#[Group('integration')]
class MPdfConverterTest extends KernelTestCase
{
    public function testConvertToPdf(): void
    {
        $kernel = self::bootKernel();
        $cacheDir = $kernel->getContainer()->getParameter('kernel.cache_dir');

        $options = [
            'foo' => 'bar',
            'additional_xmp_rdf' => '<rdf:Description rdf:about="" xmlns:zf="urn:ferd:pdfa:CrossIndustryDocument:invoice:1p0#"></rdf:Description>',
            'margin_top' => 2,
        ];

        $sut = new MPdfConverter((new FileHelperFactory($this))->create(), $cacheDir);
        $result = $sut->convertToPdf('<h1>Test</h1>', $options);
        // Yeah, that's not a real test, I know ;-)
        self::assertNotEmpty($result);
        preg_match('/\/Creator \((.*)\)/', $result, $matches);
        self::assertCount(2, $matches);
    }

    public function testAssociatedFilesPathIsStripped(): void
    {
        $kernel = self::bootKernel();
        $cacheDir = $kernel->getContainer()->getParameter('kernel.cache_dir');

        // Plant a sentinel on disk that an attacker would try to exfiltrate via
        // mPDF's `SetAssociatedFiles`. The legitimate ZUGFeRD path uses
        // `content` (pre-read bytes); we additionally pass `path` to confirm
        // it is stripped before reaching mPDF.
        $sentinelPath = tempnam(sys_get_temp_dir(), 'kimai-pdf-leak-');
        self::assertNotFalse($sentinelPath);
        $sentinelBytes = 'KIMAI_LEAK_SENTINEL_' . bin2hex(random_bytes(8));
        file_put_contents($sentinelPath, $sentinelBytes);

        $legitimateContent = 'KIMAI_LEGITIMATE_CONTENT_' . bin2hex(random_bytes(8));

        try {
            $sut = new MPdfConverter((new FileHelperFactory($this))->create(), $cacheDir);
            $result = $sut->convertToPdf('<h1>Test</h1>', [
                'associated_files' => [
                    [
                        'name' => 'attachment.txt',
                        'mime' => 'text/plain',
                        'description' => 'mixed entry',
                        'AFRelationship' => 'Alternative',
                        'path' => $sentinelPath,
                        'content' => $legitimateContent,
                    ],
                ],
            ]);
        } finally {
            @unlink($sentinelPath);
        }

        self::assertNotEmpty($result);

        // Decompress every FlateDecode stream in the produced PDF. The
        // sentinel must not appear; the explicitly-supplied `content` must.
        $allDecompressed = '';
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $result, $streams) > 0) {
            foreach ($streams[1] as $stream) {
                $decoded = @gzuncompress($stream);
                if ($decoded !== false) {
                    $allDecompressed .= $decoded;
                }
            }
        }
        self::assertStringNotContainsString($sentinelBytes, $result);
        self::assertStringNotContainsString($sentinelBytes, $allDecompressed);
        self::assertStringContainsString($legitimateContent, $allDecompressed);
    }
}
