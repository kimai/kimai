<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests;

use App\Kernel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Kernel
 */
class KernelTest extends TestCase
{
    public function testBuild(): void
    {
        $sut = new Kernel('test', false);
        self::assertStringEndsWith('var/cache/test', $sut->getCacheDir());
        self::assertStringEndsWith('var/log', $sut->getLogDir());
    }
}
