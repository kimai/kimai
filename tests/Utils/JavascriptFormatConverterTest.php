<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\JavascriptFormatConverter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\JavascriptFormatConverter
 */
class JavascriptFormatConverterTest extends TestCase
{
    public function test()
    {
        $sut = new JavascriptFormatConverter();
        self::assertEquals('DD.MM.YYYY HH:mm', $sut->convert('dd.MM.y HH:mm'));
        self::assertEquals('DD.MM.YYYY HH:mm', $sut->convert('dd.MM.yy HH:mm'));
        self::assertEquals('DD.MM.YYYY HH:mm', $sut->convert('dd.MM.yyyy HH:mm'));
        self::assertEquals('DD-MM-YYYY HH:mm', $sut->convert('dd-MM-yyyy HH:mm'));
        self::assertEquals('D/MM/YYYY HH:mm', $sut->convert('d/MM/yyyy HH:mm'));
        self::assertEquals('YYYY-MM-DD HH:mm', $sut->convert('yyyy-MM-dd HH:mm'));
        self::assertEquals('YYYY.MM.DD. HH:mm A', $sut->convert('yyyy.MM.dd. HH:mm a'));
    }
}
