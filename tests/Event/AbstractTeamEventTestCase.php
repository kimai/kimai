<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Team;
use App\Event\AbstractTeamEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractTeamEventTestCase extends TestCase
{
    abstract protected function createTeamEvent(Team $team): AbstractTeamEvent;

    public function testGetterAndSetter(): void
    {
        $team = new Team('foo');
        $sut = $this->createTeamEvent($team);

        self::assertInstanceOf(Event::class, $sut);
        self::assertSame($team, $sut->getTeam());
    }
}
