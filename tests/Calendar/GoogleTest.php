<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Calendar;

use App\Calendar\Google;
use App\Calendar\Source;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \App\Calendar\Google
 */
class GoogleTest extends TestCase
{
    public function testConstruct()
    {
        $sources = [
            (new Source())->setId('foo')->setColor('#ccc'),
            (new Source())->setId('bar')->setUri('sdsdfsdfsdfsdffd'),
        ];

        $sut = new Google('qwertzuiop1234567890');

        $this->assertEquals([], $sut->getSources());
        $this->assertEquals('qwertzuiop1234567890', $sut->getApiKey());

        $sut = new Google('ewa6347865fg908ouhpoihui7f56', $sources);

        $this->assertEquals($sources, $sut->getSources());
        $this->assertEquals('ewa6347865fg908ouhpoihui7f56', $sut->getApiKey());
    }
}
