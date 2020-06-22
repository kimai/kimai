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
    /**
     * @var DurationStringToSecondsTransformer
     */
    private $sut;

    protected function setUp(): void
    {
        $this->sut = new DurationStringToSecondsTransformer();
    }

    public function getValidTestDataTransform()
    {
        return [
            ['00:00', '0'],
            ['00:00', 0],
            ['02:00', 7213], // by default no seconds are returned
            [null, null],
        ];
    }

    public function getInvalidTestDataTransform()
    {
        return [
            [''],
            ['xxx'],
        ];
    }

    /**
     * @dataProvider getValidTestDataTransform
     */
    public function testTransform($expected, $transform)
    {
        $this->assertEquals($expected, $this->sut->transform($transform));
    }

    /**
     * @dataProvider getInvalidTestDataTransform
     */
    public function testInvalidTransformThrowsException($transform)
    {
        $this->expectException(TransformationFailedException::class);

        $this->sut->transform($transform);
    }

    public function getValidTestDataReverseTransform()
    {
        return [
            ['2h3s', 7203],
            ['00:00', 0],
            ['0', null],
            [null, null],
        ];
    }

    public function getInvalidTestDataReverseTransform()
    {
        return [
            ['xxx'],
            [':::'],
            ['0::0'],
        ];
    }

    /**
     * @dataProvider getValidTestDataReverseTransform
     */
    public function testReverseTransform($transform, $expected)
    {
        $this->assertEquals($expected, $this->sut->reverseTransform($transform));
    }

    /**
     * @dataProvider getInvalidTestDataReverseTransform
     */
    public function testInvalidReverseTransformThrowsException($transform)
    {
        $this->expectException(TransformationFailedException::class);

        $this->sut->reverseTransform($transform);
    }
}
