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
    public function testDefaultValues(): void
    {
        $sut = new Timesheet();
        self::assertEquals('timesheet', $sut->getType());
        self::assertEquals('work', $sut->getCategory());
        self::assertNull($sut->getId());
        self::assertNull($sut->getBegin());
        self::assertNull($sut->getEnd());
        self::assertTrue($sut->isBillable());
        self::assertNotNull($sut->getModifiedAt());
        self::assertSame(0, $sut->getDuration());
        self::assertSame(0, $sut->getDuration(true));
        self::assertSame(0, $sut->getDuration(false));
        self::assertNull($sut->getCalculatedDuration());
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

    public function testValueCanBeNull(): void
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

    protected function getEntity(): Timesheet
    {
        $customer = new Customer('Test Customer');

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

    public function testTags(): void
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

    public function testMetaFields(): void
    {
        $sut = new Timesheet();
        $meta = new TimesheetMeta();
        $meta->setName('foo')->setValue('bar2')->setType('test');
        self::assertInstanceOf(Timesheet::class, $sut->setMetaField($meta));
        self::assertEquals(1, $sut->getMetaFields()->count());
        $result = $sut->getMetaField('foo');
        self::assertSame($result, $meta);
        self::assertEquals('test', $result->getType());
        self::assertEquals('bar2', $result->getValue());

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

    public function testBillable(): void
    {
        $sut = new Timesheet();
        self::assertTrue($sut->isBillable());
        self::assertInstanceOf(Timesheet::class, $sut->setBillable(false));
        self::assertFalse($sut->isBillable());
        self::assertInstanceOf(Timesheet::class, $sut->setBillable(true));
        self::assertTrue($sut->isBillable());
    }

    public function testCategory(): void
    {
        $sut = new Timesheet();
        self::assertInstanceOf(Timesheet::class, $sut->setCategory(Timesheet::HOLIDAY));
        self::assertEquals('holiday', $sut->getCategory());
        self::assertInstanceOf(Timesheet::class, $sut->setCategory(Timesheet::WORK));
        self::assertEquals('work', $sut->getCategory());
        self::assertInstanceOf(Timesheet::class, $sut->setCategory(Timesheet::SICKNESS));
        self::assertEquals('sickness', $sut->getCategory());
        self::assertInstanceOf(Timesheet::class, $sut->setCategory(Timesheet::PARENTAL));
        self::assertEquals('parental', $sut->getCategory());
        self::assertInstanceOf(Timesheet::class, $sut->setCategory(Timesheet::OVERTIME));
        self::assertEquals('overtime', $sut->getCategory());

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Invalid timesheet category "foo" given, expected one of: work, holiday, sickness, parental, overtime');

        $sut->setCategory('foo');
    }

    public function testClone(): void
    {
        $sut = new Timesheet();
        self::assertNotNull($sut->getModifiedAt());
        $sut->setExported(true);
        $sut->setDescription('Invalid timesheet category "foo" given, expected one of: work, holiday, sickness, parental, overtime');

        $modifiedDate = new \DateTimeImmutable();

        $reflection = new \ReflectionClass($sut);
        $property = $reflection->getProperty('modifiedAt');
        $property->setAccessible(true);
        $property->setValue($sut, $modifiedDate);
        $property->setAccessible(false);

        self::assertEquals($modifiedDate, $sut->getModifiedAt());

        $meta = new TimesheetMeta();
        $meta->setName('blabla');
        $meta->setValue('1234567890');
        $meta->setIsVisible(false);
        $meta->setIsRequired(true);
        $sut->setMetaField($meta);

        $tag = new Tag();
        $tag->setName('bar');
        $sut->addTag($tag);
        $tag = new Tag();
        $tag->setName('foo');
        $sut->addTag($tag);

        $clone = clone $sut;

        self::assertNotNull($sut->getModifiedAt());
        self::assertNotNull($clone->getModifiedAt());

        foreach ($sut->getMetaFields() as $metaField) {
            $cloneMeta = $clone->getMetaField($metaField->getName());
            self::assertEquals($cloneMeta->getValue(), $metaField->getValue());
        }
        self::assertEquals($clone->getTags(), $sut->getTags());
        self::assertEquals($clone->getTags(), $sut->getTags());
        self::assertEquals($clone->getTagsAsArray(), $sut->getTagsAsArray());
        self::assertEquals(['bar', 'foo'], $sut->getTagsAsArray());
        self::assertEquals($clone->getBegin(), $sut->getBegin());
        self::assertEquals($clone->getEnd(), $sut->getEnd());
        self::assertFalse($clone->isExported());
    }

    public function testDuration(): void
    {
        $sut = new Timesheet();

        self::assertSame(0, $sut->getDuration());
        self::assertSame(0, $sut->getDuration(true));
        self::assertSame(0, $sut->getDuration(false));
        self::assertNull($sut->getCalculatedDuration());

        $sut->setDuration(null);

        self::assertNull($sut->getDuration());
        self::assertNull($sut->getDuration(true));
        self::assertNull($sut->getDuration(false));
        self::assertNull($sut->getCalculatedDuration());

        $begin = new \DateTime('2023-02-17 18:00:00');
        $sut->setBegin($begin);

        self::assertNull($sut->getDuration());
        self::assertNull($sut->getDuration(true));
        self::assertNull($sut->getDuration(false));
        self::assertNull($sut->getCalculatedDuration());

        $sut->setDuration(0);

        self::assertSame(0, $sut->getDuration());
        self::assertSame(0, $sut->getDuration(true));
        self::assertSame(0, $sut->getDuration(false));
        self::assertNull($sut->getCalculatedDuration());

        $end = clone $begin;
        $end->modify('+2 hours');
        $sut->setEnd($end);

        self::assertSame(0, $sut->getDuration());
        self::assertSame(0, $sut->getDuration(true));
        self::assertSame(0, $sut->getDuration(false));
        self::assertEquals(7200, $sut->getCalculatedDuration());

        $sut->setDuration(7200);

        self::assertSame(7200, $sut->getDuration());
        self::assertSame(7200, $sut->getDuration(true));
        self::assertSame(7200, $sut->getDuration(false));
        self::assertEquals(7200, $sut->getCalculatedDuration());

        $end = clone $begin;
        $end->modify('+1 hours');
        $sut->setEnd($end);

        self::assertSame(7200, $sut->getDuration());
        self::assertSame(7200, $sut->getDuration(true));
        self::assertSame(7200, $sut->getDuration(false));
        self::assertEquals(3600, $sut->getCalculatedDuration());
    }
}
