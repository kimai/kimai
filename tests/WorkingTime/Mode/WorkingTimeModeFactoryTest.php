<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\WorkingTime\Mode;

use App\Entity\User;
use App\WorkingTime\Mode\WorkingTimeModeDay;
use App\WorkingTime\Mode\WorkingTimeModeFactory;
use App\WorkingTime\Mode\WorkingTimeModeNone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

#[CoversClass(WorkingTimeModeFactory::class)]
class WorkingTimeModeFactoryTest extends TestCase
{
    public function testDefaults(): void
    {
        $none = new WorkingTimeModeNone();
        $day = new WorkingTimeModeDay();
        $modes = [$none, $day];
        $sut = new WorkingTimeModeFactory($modes, new NullLogger());
        self::assertEquals($modes, $sut->getAll());
        self::assertSame($none, $sut->getMode('none'));
        self::assertSame($day, $sut->getMode('day'));

        $user = new User();
        $user->setWorkContractMode('day');
        self::assertSame($day, $sut->getModeForUser($user));
    }

    public function testFallbackMode(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');
        $modes = [new WorkingTimeModeNone(), new WorkingTimeModeDay()];
        $sut = new WorkingTimeModeFactory($modes, $logger);

        $user = new User();
        $user->setUsername('foo-bar');
        $user->setWorkContractMode('foo');
        self::assertInstanceOf(WorkingTimeModeNone::class, $sut->getModeForUser($user));
    }

    public function testException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown working contract mode: foo');

        $sut = new WorkingTimeModeFactory([], new NullLogger());
        $sut->getMode('foo');
    }
}
