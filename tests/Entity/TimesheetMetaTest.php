<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\EntityWithMetaFields;
use App\Entity\MetaTableTypeInterface;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\TimesheetMeta;

/**
 * @covers \App\Entity\TimesheetMeta
 */
class TimesheetMetaTest extends AbstractMetaEntityTest
{
    protected function getEntity(): EntityWithMetaFields
    {
        return new Timesheet();
    }

    protected function getMetaEntity(): MetaTableTypeInterface
    {
        return new TimesheetMeta();
    }

    public function testSetEntityThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected instanceof Timesheet, received "App\Entity\Project"');

        $sut = new TimesheetMeta();
        $sut->setEntity(new Project());
    }
}
