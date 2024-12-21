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
class ActivityTest extends AbstractEntityTestCase
{
    public function testDefaultValues(): void
    {
        $sut = new Activity();
        self::assertNull($sut->getId());
        self::assertNull($sut->getProject());
        self::assertNull($sut->getName());
        self::assertNull($sut->getComment());
        self::assertNull($sut->getInvoiceText());
        self::assertTrue($sut->isVisible());
        self::assertTrue($sut->isBillable());
        self::assertTrue($sut->isGlobal());
        self::assertNull($sut->getColor());
        self::assertFalse($sut->hasColor());
        self::assertInstanceOf(Collection::class, $sut->getMetaFields());
        self::assertEquals(0, $sut->getMetaFields()->count());
        self::assertNull($sut->getMetaField('foo'));
        self::assertInstanceOf(Collection::class, $sut->getTeams());
    }

    public function testBudgets(): void
    {
        $this->assertBudget(new Activity());
    }

    public function testSetterAndGetter(): void
    {
        $sut = new Activity();
        self::assertInstanceOf(Activity::class, $sut->setName('foo-bar'));
        self::assertEquals('foo-bar', $sut->getName());
        self::assertEquals('foo-bar', (string) $sut);

        self::assertInstanceOf(Activity::class, $sut->setVisible(false));
        self::assertFalse($sut->isVisible());

        $sut->setVisible(false);
        self::assertFalse($sut->isVisible());
        $sut->setVisible(true);
        self::assertTrue($sut->isVisible());

        self::assertInstanceOf(Activity::class, $sut->setComment('hello world'));
        self::assertEquals('hello world', $sut->getComment());

        $sut->setInvoiceText('very long invoice text comment 12324');
        self::assertEquals('very long invoice text comment 12324', $sut->getInvoiceText());

        self::assertFalse($sut->hasColor());
        $sut->setColor('#fffccc');
        self::assertEquals('#fffccc', $sut->getColor());
        self::assertTrue($sut->hasColor());

        $sut->setColor(Constants::DEFAULT_COLOR);
        self::assertNull($sut->getColor());
        self::assertFalse($sut->hasColor());

        self::assertTrue($sut->isGlobal());
        self::assertInstanceOf(Activity::class, $sut->setProject(new Project()));
        self::assertFalse($sut->isGlobal());
    }

    public function testMetaFields(): void
    {
        $sut = new Activity();
        $meta = new ActivityMeta();
        $meta->setName('foo')->setValue('bar2')->setType('test');
        self::assertInstanceOf(Activity::class, $sut->setMetaField($meta));
        self::assertEquals(1, $sut->getMetaFields()->count());
        $result = $sut->getMetaField('foo');
        self::assertSame($result, $meta);
        self::assertEquals('test', $result->getType());
        self::assertEquals('bar2', $result->getValue());

        $meta2 = new ActivityMeta();
        $meta2->setName('foo')->setValue('bar')->setType('test2');
        self::assertInstanceOf(Activity::class, $sut->setMetaField($meta2));
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

    public function testTeams(): void
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

    public function testExportAnnotations(): void
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
            ['activity_number', 'string'],
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

    public function testClone(): void
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
