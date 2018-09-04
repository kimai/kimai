<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;

/**
 * @covers \App\Entity\Timesheet
 */
class TimesheetTest extends AbstractEntityTest
{
    public function testDefaultValues()
    {
        $sut = new Timesheet();
        $this->assertNull($sut->getId());
        $this->assertNull($sut->getBegin());
        $this->assertNull($sut->getEnd());
        $this->assertSame(0, $sut->getDuration());
        $this->assertNull($sut->getUser());
        $this->assertNull($sut->getActivity());
        $this->assertNull($sut->getDescription());
        $this->assertSame(0.00, $sut->getRate());
        $this->assertNull($sut->getFixedRate());
        $this->assertNull($sut->getHourlyRate());
    }

    protected function getEntity()
    {
        $customer = new Customer();
        $customer->setName('Test Customer');

        $project = new Project();
        $project->setName('Test Project');
        $project->setCustomer($customer);

        $activity = new Activity();
        $activity->setName('Test');
        $activity->setProject($project);

        $entity = new Timesheet();
        $entity->setUser(new User());
        $entity->setActivity($activity);

        return $entity;
    }

    public function testValidationEndNotEarlierThanBegin()
    {
        $entity = $this->getEntity();
        $begin = new \DateTime();
        $end = clone $begin;
        $end = $end->modify('-1 second');
        $entity->setBegin($begin);
        $entity->setEnd($end);

        $this->assertHasViolationForField($entity, 'end');

        // allow same begin and end
        $entity = $this->getEntity();
        $begin = new \DateTime();
        $end = clone $begin;
        $entity->setBegin($begin);
        $entity->setEnd($end);

        $this->assertHasViolationForField($entity, []);
    }

    public function testDurationMustBeGreatorOrEqualThanZero()
    {
        $entity = $this->getEntity();
        $begin = new \DateTime();
        $end = clone $begin;
        $entity->setBegin($begin);
        $entity->setEnd($end);
        $entity->setDuration(-1);

        $this->assertHasViolationForField($entity, 'duration');

        // allow zero duration
        $entity = $this->getEntity();
        $begin = new \DateTime();
        $end = clone $begin;
        $entity->setBegin($begin);
        $entity->setEnd($end);
        $entity->setDuration(0);

        $this->assertHasViolationForField($entity, []);
    }
}
