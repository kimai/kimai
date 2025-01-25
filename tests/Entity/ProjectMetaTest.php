<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Customer;
use App\Entity\EntityWithMetaFields;
use App\Entity\MetaTableTypeInterface;
use App\Entity\Project;
use App\Entity\ProjectMeta;

/**
 * @covers \App\Entity\ProjectMeta
 */
class ProjectMetaTest extends AbstractMetaEntityTestCase
{
    protected function getEntity(): EntityWithMetaFields
    {
        return new Project();
    }

    protected function getMetaEntity(): MetaTableTypeInterface
    {
        return new ProjectMeta();
    }

    public function testSetEntityThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected instanceof Project, received "App\Entity\Customer"');

        $sut = new ProjectMeta();
        $sut->setEntity(new Customer('foo'));
    }
}
