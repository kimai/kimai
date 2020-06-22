<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Loader;

use App\Repository\Loader\DefaultLoader;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Repository\Loader\DefaultLoader
 */
class DefaultLoaderTest extends TestCase
{
    public function testLoadResults()
    {
        $sut = new DefaultLoader();

        $input = [];
        $sut->loadResults($input);

        self::assertEquals([], $input);
    }
}
