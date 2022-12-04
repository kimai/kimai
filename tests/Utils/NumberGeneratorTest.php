<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\NumberGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\NumberGenerator
 */
class NumberGeneratorTest extends TestCase
{
    /**
     * @dataProvider getTestData
     * @param string $format
     * @param array<mixed> $options
     * @param string $expected
     * @param int $startWith
     * @return void
     */
    public function testGenerateNumber(string $format, array $options, string $expected, int $startWith): void
    {
        $sut = new NumberGenerator($format, function (string $originalFormat, string $format, int $increaseBy) use ($options) {
            if (\array_key_exists($format, $options)) {
                $value = $options[$format];
                if (\is_int($value)) {
                    $value += $increaseBy;
                }

                return $value;
            }

            return $originalFormat;
        });

        self::assertEquals($expected, $sut->getNumber($startWith));
    }

    /**
     * @return array<mixed>
     */
    public function getTestData(): array
    {
        return [
            ['DEMO-{XXX}-{known,5}', ['known' => 123], 'DEMO-{XXX}-00124', 0],
            ['DEMO-{XXX}-{known,4}', ['known' => 123, 'XXX' => 'FOO!'], 'DEMO-FOO!-0222', 99],
            ['DEMO-{XXX}-{known+11}', ['known' => 123, 'XXX' => 'FOO!'], 'DEMO-FOO!-135', 1],
            ['{XXX}-{known-11}', ['known' => 123, 'XXX' => 'A'], 'A-113', 1],
        ];
    }
}
