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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Pdf\MPdfConverter
 * @group integration
 */
class MPdfConverterTest extends KernelTestCase
{
    public function test()
    {
        $kernel = self::bootKernel();
        $cacheDir = $kernel->getContainer()->getParameter('kernel.cache_dir');

        $sut = new MPdfConverter((new FileHelperFactory($this))->create(), $cacheDir);
        $result = $sut->convertToPdf('<h1>Test</h1>');
        // Yeah, thats not a real test, I know ;-)
        self::assertNotEmpty($result);
        preg_match('/\/Creator \((.*)\)/', $result, $matches);
        self::assertCount(2, $matches);
    }
}
