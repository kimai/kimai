<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Activity;
use App\Entity\ActivityMeta;
use App\Entity\EntityWithMetaFields;
use App\Entity\MetaTableTypeInterface;
use App\Entity\Timesheet;

/**
 * @covers \App\Entity\ActivityMeta
 */
class ActivityMetaTest extends AbstractMetaEntityTest
{
    protected function getEntity(): EntityWithMetaFields
    {
        return new Activity();
    }

    protected function getMetaEntity(): MetaTableTypeInterface
    {
        return new ActivityMeta();
    }

    public function testSetEntityThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected instanceof Activity, received "App\Entity\Timesheet"');

        $sut = new ActivityMeta();
        $sut->setEntity(new Timesheet());
    }
}
