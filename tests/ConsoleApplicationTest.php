<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests;

use App\ConsoleApplication;
use App\Constants;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @covers \App\ConsoleApplication
 */
class ConsoleApplicationTest extends TestCase
{
    public function testVersion(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $sut = new ConsoleApplication($kernel);
        self::assertEquals(Constants::SOFTWARE, $sut->getName());
        self::assertEquals(Constants::VERSION, $sut->getVersion());
        self::assertEquals(sprintf('%s <info>%s</info> (env: <comment></>, debug: <comment>false</>)', Constants::SOFTWARE, Constants::VERSION), $sut->getLongVersion());
    }
}
