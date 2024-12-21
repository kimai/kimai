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
use App\Event\ProjectDetailControllerEvent;

/**
 * @covers \App\Event\AbstractProjectEvent
 * @covers \App\Event\ProjectDetailControllerEvent
 */
class ProjectDetailControllerEventTest extends AbstractProjectEventTestCase
{
    protected function createProjectEvent(Project $project): AbstractProjectEvent
    {
        return new ProjectDetailControllerEvent($project);
    }

    public function testController(): void
    {
        /** @var ProjectDetailControllerEvent $event */
        $event = $this->createProjectEvent(new Project());
        $event->addController('Foo\\Bar::helloWorld');
        self::assertEquals(['Foo\\Bar::helloWorld'], $event->getController());
    }
}
