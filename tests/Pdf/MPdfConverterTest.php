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
}
