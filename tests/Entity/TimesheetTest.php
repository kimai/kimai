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
use App\Entity\TimesheetMeta;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
        $this->assertInstanceOf(Collection::class, $sut->getMetaFields());
        $this->assertEquals(0, $sut->getMetaFields()->count());
        $this->assertNull($sut->getMetaField('foo'));
    }
    
    public function testDurationCannotBeNull()
    {
        $sut = new Timesheet();
        self::assertEquals(0, $sut->getDuration());
        $sut->setDuration(null);
        self::assertEquals(0, $sut->getDuration());
        $sut->setDuration(-1);
        self::assertEquals(0, $sut->getDuration());
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

    public function testMetaFields()
    {
        $sut = new Timesheet();
        $meta = new TimesheetMeta();
        $meta->setName('foo')->setValue('bar')->setType('test');
        $this->assertInstanceOf(Timesheet::class, $sut->setMetaField($meta));
        self::assertEquals(1, $sut->getMetaFields()->count());
        $result = $sut->getMetaField('foo');
        self::assertSame($result, $meta);
        self::assertEquals('test', $result->getType());

        $meta2 = new TimesheetMeta();
        $meta2->setName('foo')->setValue('bar')->setType('test2');
        $this->assertInstanceOf(Timesheet::class, $sut->setMetaField($meta2));
        self::assertEquals(1, $sut->getMetaFields()->count());
        self::assertCount(0, $sut->getVisibleMetaFields());

        $result = $sut->getMetaField('foo');
        self::assertSame($result, $meta);
        self::assertEquals('test2', $result->getType());

        $sut->setMetaField((new TimesheetMeta())->setName('blub')->setIsVisible(true));
        $sut->setMetaField((new TimesheetMeta())->setName('blab')->setIsVisible(true));
        self::assertEquals(3, $sut->getMetaFields()->count());
        self::assertCount(2, $sut->getVisibleMetaFields());
    }
}
