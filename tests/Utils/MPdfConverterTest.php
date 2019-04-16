<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\MPdfConverter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Utils\MPdfConverter
 */
class MPdfConverterTest extends KernelTestCase
{
    public function unicode_hex($unicode_dec)
    {
        return (sprintf('%05s', strtoupper(dechex($unicode_dec))));
    }

    public function test()
    {
        $kernel = self::bootKernel();
        $cacheDir = $kernel->getContainer()->getParameter('kernel.cache_dir');

        $sut = new MPdfConverter($cacheDir);
        $result = $sut->convertToPdf('<h1>Test</h1>');
        // Yeah, thats not a real test, I know ;-)
        $this->assertNotEmpty($result);
        preg_match('/\/Creator \((.*)\)/', $result, $matches);
        $this->assertCount(2, $matches);
    }
}
