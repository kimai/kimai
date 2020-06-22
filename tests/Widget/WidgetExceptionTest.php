<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget;

use App\Widget\WidgetException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Widget\WidgetException
 */
class WidgetExceptionTest extends TestCase
{
    public function testConstruct()
    {
        $ex = new WidgetException();
        self::assertInstanceOf(\Exception::class, $ex);
    }
}
