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
use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Timesheet
 */
class TimesheetTest extends TestCase
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
        $this->assertNull($sut->getProject());
        $this->assertNull($sut->getDescription());
        $this->assertSame(0.00, $sut->getRate());
        $this->assertNull($sut->getFixedRate());
        $this->assertNull($sut->getHourlyRate());
        $this->assertEquals(new ArrayCollection(), $sut->getTags());
        $this->assertEquals([], $sut->getTagsAsArray());

        $this->assertInstanceOf(Timesheet::class, $sut->setFixedRate(13.47));
        $this->assertEquals(13.47, $sut->getFixedRate());
        $this->assertInstanceOf(Timesheet::class, $sut->setHourlyRate(99));
        $this->assertEquals(99, $sut->getHourlyRate());
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
        $entity->setProject($project);

        return $entity;
    }

    public function testTags()
    {
        $sut = new Timesheet();
        $tag = new Tag();
        $tag->setName('bar');
        $tag1 = new Tag();
        $tag1->setName('foo');

        $this->assertEmpty($sut->getTags());

        $sut->addTag($tag);
        $sut->addTag($tag1);

        $this->assertEquals([0 => 'bar', 1 => 'foo'], $sut->getTagsAsArray());
        $this->assertEquals(new ArrayCollection([$tag, $tag1]), $sut->getTags());

        $sut->removeTag($tag);
        $this->assertEquals([1 => 'foo'], $sut->getTagsAsArray());

        $sut->removeTag($tag1);
        $this->assertEmpty($sut->getTags());
    }
}
