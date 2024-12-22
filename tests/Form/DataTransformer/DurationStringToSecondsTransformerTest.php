<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\DurationStringToSecondsTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @covers \App\Form\DataTransformer\DurationStringToSecondsTransformer
 */
class DurationStringToSecondsTransformerTest extends TestCase
{
    private DurationStringToSecondsTransformer $sut;

    protected function setUp(): void
    {
        $this->sut = new DurationStringToSecondsTransformer();
    }

    public static function getValidTestDataTransform(): array
    {
        return [
            ['0:00', '0'],
            ['123:00', '442800'],
            ['123:00', 442800],
            ['0:00', 0],
            ['2:00', 7213], // by default no seconds are returned
            [null, null],
        ];
    }

    public static function getInvalidTestDataTransform(): array
    {
        return [
            [''],
            ['xxx'],
        ];
    }

    /**
     * @dataProvider getValidTestDataTransform
     */
    public function testTransform($expected, $transform): void
    {
        self::assertEquals($expected, $this->sut->transform($transform));
    }

    /**
     * @dataProvider getInvalidTestDataTransform
     */
    public function testInvalidTransformThrowsException($transform): void
    {
        $value = $this->sut->transform($transform);
        self::assertNull($value);
    }

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    public static function getValidTestDataReverseTransform(): array
    {
        return [
            ['2h3s', 7203],
            ['0:00', 0],
            ['0', null],
            [null, null],
            ['87600000000:00:00', 315360000000000],
            [87600000000, 315360000000000], // int will be treated we could argue if this is a correct behavior
            [87599999999.5, 315359999998200], // only int can be used as hourly input
        ];
    }

    /**
     * @return array<int, array<int, string|int>>
     */
    public static function getInvalidTestDataReverseTransform(): array
    {
        return [
            ['xxx'],
            [':::'],
            ['0::0'],
            // too large
            ['87600000000:00:01'],
            // too large
            [315360000000001],
        ];
    }

    /**
     * @dataProvider getValidTestDataReverseTransform
     */
    public function testReverseTransform($transform, $expected): void
    {
        self::assertEquals($expected, $this->sut->reverseTransform($transform));
    }

    /**
     * @dataProvider getInvalidTestDataReverseTransform
     */
    public function testInvalidReverseTransformThrowsException($transform): void
    {
        $this->expectException(TransformationFailedException::class);

        $this->sut->reverseTransform($transform);
    }
}
