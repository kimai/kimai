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
        self::assertNull($sut->getId());
        self::assertNull($sut->getBegin());
        self::assertNull($sut->getEnd());
        self::assertSame(0, $sut->getDuration());
        self::assertNull($sut->getUser());
        self::assertNull($sut->getActivity());
        self::assertNull($sut->getProject());
        self::assertNull($sut->getDescription());
        self::assertSame(0.00, $sut->getRate());
        self::assertNull($sut->getFixedRate());
        self::assertNull($sut->getInternalRate());
        self::assertNull($sut->getHourlyRate());
        self::assertEquals(new ArrayCollection(), $sut->getTags());
        self::assertEquals([], $sut->getTagsAsArray());
        self::assertInstanceOf(Timesheet::class, $sut->setFixedRate(13.47));
        self::assertEquals(13.47, $sut->getFixedRate());
        self::assertInstanceOf(Timesheet::class, $sut->setInternalRate(999.99));
        self::assertEquals(999.99, $sut->getInternalRate());
        self::assertInstanceOf(Timesheet::class, $sut->setHourlyRate(99));
        self::assertEquals(99, $sut->getHourlyRate());
        self::assertInstanceOf(Collection::class, $sut->getMetaFields());
        self::assertEquals(0, $sut->getMetaFields()->count());
        self::assertNull($sut->getMetaField('foo'));
    }

    public function testValueCanBeNull()
    {
        $sut = new Timesheet();
        self::assertEquals(0, $sut->getDuration());
        $sut->setDuration(null);
        self::assertNull($sut->getDuration());
        $sut->setDuration(-1);
        self::assertEquals(-1, $sut->getDuration());

        $sut->setInternalRate(1);
        self::assertEquals(1, $sut->getInternalRate());
        $sut->setInternalRate(null);
        self::assertNull($sut->getInternalRate());
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

        self::assertEquals([0 => 'bar', 1 => 'foo'], $sut->getTagsAsArray());
        self::assertEquals(new ArrayCollection([$tag, $tag1]), $sut->getTags());

        $sut->removeTag($tag);
        self::assertEquals([1 => 'foo'], $sut->getTagsAsArray());

        $sut->removeTag($tag1);
        $this->assertEmpty($sut->getTags());
    }

    public function testMetaFields()
    {
        $sut = new Timesheet();
        $meta = new TimesheetMeta();
        $meta->setName('foo')->setValue('bar')->setType('test');
        self::assertInstanceOf(Timesheet::class, $sut->setMetaField($meta));
        self::assertEquals(1, $sut->getMetaFields()->count());
        $result = $sut->getMetaField('foo');
        self::assertSame($result, $meta);
        self::assertEquals('test', $result->getType());

        $meta2 = new TimesheetMeta();
        $meta2->setName('foo')->setValue('bar')->setType('test2');
        self::assertInstanceOf(Timesheet::class, $sut->setMetaField($meta2));
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
