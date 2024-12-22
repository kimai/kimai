<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Calendar;

use App\Calendar\Google;
use App\Calendar\GoogleSource;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Calendar\Google
 */
class GoogleTest extends TestCase
{
    public function testConstruct(): void
    {
        $sources = [
            new GoogleSource('foo', '', '#ccc'),
            new GoogleSource('bar', 'sdsdfsdfsdfsdffd'),
        ];

        $sut = new Google('qwertzuiop1234567890');

        self::assertEquals([], $sut->getSources());
        self::assertEquals('qwertzuiop1234567890', $sut->getApiKey());

        $sut = new Google('ewa6347865fg908ouhpoihui7f56', $sources);

        self::assertEquals($sources, $sut->getSources());
        self::assertEquals('ewa6347865fg908ouhpoihui7f56', $sut->getApiKey());
    }
}
