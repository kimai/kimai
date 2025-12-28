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
use App\Event\ProjectDeleteEvent;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProjectDeleteEvent::class)]
class ProjectDeleteEventTest extends AbstractProjectEventTestCase
{
    protected function createProjectEvent(Project $project): AbstractProjectEvent
    {
        return new ProjectDeleteEvent($project);
    }

    public function testReplacement(): void
    {
        $entity = new Project();
        $entity->setName('project 1');
        $replacement = new Project();
        $replacement->setName('project 2');

        $sut = new ProjectDeleteEvent($entity, $replacement);

        self::assertSame($entity, $sut->getProject());
        self::assertSame($replacement, $sut->getReplacementProject());
    }
}
