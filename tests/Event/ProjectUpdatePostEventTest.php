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
use App\Event\ProjectUpdatePostEvent;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractProjectEvent::class)]
#[CoversClass(ProjectUpdatePostEvent::class)]
class ProjectUpdatePostEventTest extends AbstractProjectEventTestCase
{
    protected function createProjectEvent(Project $project): AbstractProjectEvent
    {
        return new ProjectUpdatePostEvent($project);
    }
}
