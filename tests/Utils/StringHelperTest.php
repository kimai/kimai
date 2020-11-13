<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\StringHelper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\StringHelper
 */
class StringHelperTest extends TestCase
{
    public function testEnsureMaxLength()
    {
        self::assertNull(StringHelper::ensureMaxLength(null, 10));
        self::assertEquals('', StringHelper::ensureMaxLength('', 10));
        self::assertEquals(1, mb_strlen(StringHelper::ensureMaxLength('까깨꺄', 1)));
        self::assertEquals(3, mb_strlen(StringHelper::ensureMaxLength('까깨꺄', 10)));
        self::assertEquals(5, mb_strlen(StringHelper::ensureMaxLength('xxxxx', 10)));
        self::assertEquals(10, mb_strlen(StringHelper::ensureMaxLength('xxxxxxxxxx', 10)));
        self::assertEquals(10, mb_strlen(StringHelper::ensureMaxLength('까깨꺄꺠꺼께껴꼐꼬꽈sssss', 10)));
        self::assertEquals(10, mb_strlen(StringHelper::ensureMaxLength('까깨꺄꺠꺼께껴꼐꼬꽈꼬꽈', 10)));
    }
}
