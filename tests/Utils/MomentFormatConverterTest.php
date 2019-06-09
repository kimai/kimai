<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\MomentFormatConverter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\MomentFormatConverter
 */
class MomentFormatConverterTest extends TestCase
{
    public function test()
    {
        $sut = new MomentFormatConverter();
        $this->assertEquals('DD.MM.YYYY HH:mm', $sut->convert('dd.MM.yyyy HH:mm'));
        $this->assertEquals('DD-MM-YYYY HH:mm', $sut->convert('dd-MM-yyyy HH:mm'));
        $this->assertEquals('DD/MM/YYYY HH:mm', $sut->convert('dd/MM/yyyy HH:mm'));
        $this->assertEquals('YYYY-MM-DD HH:mm', $sut->convert('yyyy-MM-dd HH:mm'));
        $this->assertEquals('YYYY.MM.DD. HH:mm', $sut->convert('yyyy.MM.dd. HH:mm'));
    }
}
