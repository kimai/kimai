<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Constants;
use App\Entity\Activity;
use App\Entity\ActivityMeta;
use App\Entity\Project;
use App\Entity\Team;
use App\Export\Spreadsheet\ColumnDefinition;
use App\Export\Spreadsheet\Extractor\AnnotationExtractor;
use Doctrine\Common\Collections\Collection;

/**
 * @covers \App\Entity\Activity
 */
class ActivityTest extends AbstractEntityTest
{
    public function testDefaultValues()
    {
        $sut = new Activity();
        $this->assertNull($sut->getId());
        $this->assertNull($sut->getProject());
        $this->assertNull($sut->getName());
        $this->assertNull($sut->getComment());
        $this->assertNull($sut->getInvoiceText());
        $this->assertTrue($sut->isVisible());
        $this->assertTrue($sut->isBillable());
        $this->assertTrue($sut->isGlobal());
        $this->assertNull($sut->getColor());
        self::assertFalse($sut->hasColor());
        $this->assertInstanceOf(Collection::class, $sut->getMetaFields());
        $this->assertEquals(0, $sut->getMetaFields()->count());
        $this->assertNull($sut->getMetaField('foo'));
        $this->assertInstanceOf(Collection::class, $sut->getTeams());
    }

    public function testBudgets()
    {
        $this->assertBudget(new Activity());
    }

    public function testSetterAndGetter()
    {
        $sut = new Activity();
        $this->assertInstanceOf(Activity::class, $sut->setName('foo-bar'));
        $this->assertEquals('foo-bar', $sut->getName());
        $this->assertEquals('foo-bar', (string) $sut);

        $this->assertInstanceOf(Activity::class, $sut->setVisible(false));
        $this->assertFalse($sut->isVisible());

        $sut->setVisible(false);
        self::assertFalse($sut->isVisible());
        $sut->setVisible(true);
        self::assertTrue($sut->isVisible());

        $this->assertInstanceOf(Activity::class, $sut->setComment('hello world'));
        $this->assertEquals('hello world', $sut->getComment());

        $sut->setInvoiceText('very long invoice text comment 12324');
        self::assertEquals('very long invoice text comment 12324', $sut->getInvoiceText());

        self::assertFalse($sut->hasColor());
        $sut->setColor('#fffccc');
        $this->assertEquals('#fffccc', $sut->getColor());
        self::assertTrue($sut->hasColor());

        $sut->setColor(Constants::DEFAULT_COLOR);
        $this->assertNull($sut->getColor());
        self::assertFalse($sut->hasColor());

        $this->assertTrue($sut->isGlobal());
        $this->assertInstanceOf(Activity::class, $sut->setProject(new Project()));
        $this->assertFalse($sut->isGlobal());
    }

    public function testMetaFields()
    {
        $sut = new Activity();
        $meta = new ActivityMeta();
        $meta->setName('foo')->setValue('bar2')->setType('test');
        $this->assertInstanceOf(Activity::class, $sut->setMetaField($meta));
        self::assertEquals(1, $sut->getMetaFields()->count());
        $result = $sut->getMetaField('foo');
        self::assertSame($result, $meta);
        self::assertEquals('test', $result->getType());
        self::assertEquals('bar2', $result->getValue());

        $meta2 = new ActivityMeta();
        $meta2->setName('foo')->setValue('bar')->setType('test2');
        $this->assertInstanceOf(Activity::class, $sut->setMetaField($meta2));
        self::assertEquals(1, $sut->getMetaFields()->count());
        self::assertCount(0, $sut->getVisibleMetaFields());

        $result = $sut->getMetaField('foo');
        self::assertSame($result, $meta);
        self::assertEquals('test2', $result->getType());

        $sut->setMetaField((new ActivityMeta())->setName('blub')->setIsVisible(true));
        $sut->setMetaField((new ActivityMeta())->setName('blab')->setIsVisible(true));
        self::assertEquals(3, $sut->getMetaFields()->count());
        self::assertCount(2, $sut->getVisibleMetaFields());
    }

    public function testTeams()
    {
        $sut = new Activity();
        $team = new Team('foo');
        self::assertEmpty($sut->getTeams());
        self::assertEmpty($team->getActivities());

        $sut->addTeam($team);
        self::assertCount(1, $sut->getTeams());
        self::assertCount(1, $team->getActivities());
        self::assertSame($team, $sut->getTeams()[0]);
        self::assertSame($sut, $team->getActivities()[0]);

        // test remove unknown team doesn't do anything
        $sut->removeTeam(new Team('foo'));
        self::assertCount(1, $sut->getTeams());
        self::assertCount(1, $team->getActivities());

        $sut->removeTeam($team);
        self::assertCount(0, $sut->getTeams());
        self::assertCount(0, $team->getActivities());
    }

    public function testExportAnnotations()
    {
        $sut = new AnnotationExtractor();

        $columns = $sut->extract(Activity::class);

        self::assertIsArray($columns);

        $expected = [
            ['id', 'integer'],
            ['name', 'string'],
            ['project', 'string'],
            ['budget', 'float'],
            ['timeBudget', 'duration'],
            ['budgetType', 'string'],
            ['color', 'string'],
            ['visible', 'boolean'],
            ['comment', 'string'],
            ['billable', 'boolean'],
        ];

        self::assertCount(\count($expected), $columns);

        foreach ($columns as $column) {
            self::assertInstanceOf(ColumnDefinition::class, $column);
        }

        $i = 0;

        foreach ($expected as $item) {
            $column = $columns[$i++];
            self::assertEquals($item[0], $column->getLabel());
            self::assertEquals($item[1], $column->getType(), 'Wrong type for field: ' . $item[0]);
        }
    }

    public function testClone()
    {
        $sut = new Activity();
        $sut->setName('activity1111');
        $sut->setComment('DE-0123456789');

        $project = new Project();
        $project->setName('foo');
        $project->setOrderNumber('1234567890');
        $project->setBudget(123.45);
        $project->setTimeBudget(12345);
        $project->setVisible(false);
        $project->setEnd(new \DateTime());
        $project->setColor('#ccc');

        $sut->setProject($project);

        $team = new Team('foo');
        $sut->addTeam($team);

        $meta = new ActivityMeta();
        $meta->setName('blabla');
        $meta->setValue('1234567890');
        $meta->setIsVisible(false);
        $meta->setIsRequired(true);
        $sut->setMetaField($meta);

        $clone = clone $sut;

        foreach ($sut->getMetaFields() as $metaField) {
            $cloneMeta = $clone->getMetaField($metaField->getName());
            self::assertEquals($cloneMeta->getValue(), $metaField->getValue());
        }
        self::assertEquals($clone->getBudget(), $sut->getBudget());
        self::assertEquals($clone->getTimeBudget(), $sut->getTimeBudget());
        self::assertEquals($clone->getComment(), $sut->getComment());
        self::assertEquals($clone->getColor(), $sut->getColor());
        self::assertEquals('DE-0123456789', $clone->getComment());
        self::assertEquals('activity1111', $clone->getName());
    }
}
