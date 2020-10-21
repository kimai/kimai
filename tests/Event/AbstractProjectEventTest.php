<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Project;
use App\Event\AbstractProjectEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractProjectEventTest extends TestCase
{
    abstract protected function createProjectEvent(Project $project): AbstractProjectEvent;

    public function testGetterAndSetter()
    {
        $project = new Project();
        $sut = $this->createProjectEvent($project);

        self::assertInstanceOf(Event::class, $sut);
        self::assertSame($project, $sut->getProject());
    }
}
