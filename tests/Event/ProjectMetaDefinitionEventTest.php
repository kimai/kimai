<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Project;
use App\Event\ProjectMetaDefinitionEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\ProjectMetaDefinitionEvent
 */
class ProjectMetaDefinitionEventTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $project = new Project();
        $sut = new ProjectMetaDefinitionEvent($project);
        $this->assertSame($project, $sut->getEntity());
    }
}
