<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Constants;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\ProjectMeta;
use App\Entity\Team;
use App\Export\Spreadsheet\ColumnDefinition;
use App\Export\Spreadsheet\Extractor\AnnotationExtractor;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Project
 */
class ProjectTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new Project();
        self::assertNull($sut->getId());
        self::assertNull($sut->getCustomer());
        self::assertNull($sut->getName());
        self::assertNull($sut->getOrderNumber());
        self::assertNull($sut->getOrderDate());
        self::assertNull($sut->getStart());
        self::assertNull($sut->getEnd());
        self::assertNull($sut->getComment());
        self::assertTrue($sut->isVisible());
        self::assertNull($sut->getColor());
        self::assertEquals(0.0, $sut->getBudget());
        self::assertEquals(0, $sut->getTimeBudget());
        self::assertInstanceOf(Collection::class, $sut->getMetaFields());
        self::assertEquals(0, $sut->getMetaFields()->count());
        self::assertNull($sut->getMetaField('foo'));
        self::assertInstanceOf(Collection::class, $sut->getTeams());
        self::assertEquals(0, $sut->getTeams()->count());
    }

    public function testSetterAndGetter()
    {
        $sut = new Project();

        $customer = (new Customer())->setName('customer');
        self::assertInstanceOf(Project::class, $sut->setCustomer($customer));
        self::assertSame($customer, $sut->getCustomer());

        self::assertInstanceOf(Project::class, $sut->setName('123456789'));
        self::assertEquals('123456789', (string) $sut);

        self::assertInstanceOf(Project::class, $sut->setOrderNumber('123456789'));
        self::assertEquals('123456789', $sut->getOrderNumber());

        $dateTime = new \DateTime('-1 year');
        self::assertInstanceOf(Project::class, $sut->setOrderDate($dateTime));
        self::assertSame($dateTime, $sut->getOrderDate());
        self::assertInstanceOf(Project::class, $sut->setOrderDate(null));
        self::assertNull($sut->getOrderDate());

        self::assertInstanceOf(Project::class, $sut->setStart($dateTime));
        self::assertSame($dateTime, $sut->getStart());
        self::assertInstanceOf(Project::class, $sut->setStart(null));
        self::assertNull($sut->getStart());

        self::assertInstanceOf(Project::class, $sut->setEnd($dateTime));
        self::assertSame($dateTime, $sut->getEnd());
        self::assertInstanceOf(Project::class, $sut->setEnd(null));
        self::assertNull($sut->getEnd());

        self::assertInstanceOf(Project::class, $sut->setComment('a comment'));
        self::assertEquals('a comment', $sut->getComment());

        self::assertInstanceOf(Project::class, $sut->setColor('#fffccc'));
        self::assertEquals('#fffccc', $sut->getColor());

        self::assertInstanceOf(Project::class, $sut->setColor(Constants::DEFAULT_COLOR));
        self::assertNull($sut->getColor());

        self::assertInstanceOf(Project::class, $sut->setVisible(false));
        self::assertFalse($sut->isVisible());

        self::assertInstanceOf(Project::class, $sut->setBudget(12345.67));
        self::assertEquals(12345.67, $sut->getBudget());

        self::assertInstanceOf(Project::class, $sut->setTimeBudget(937321));
        self::assertEquals(937321, $sut->getTimeBudget());
    }

    public function testMetaFields()
    {
        $sut = new Project();
        $meta = new ProjectMeta();
        $meta->setName('foo')->setValue('bar')->setType('test');
        self::assertInstanceOf(Project::class, $sut->setMetaField($meta));
        self::assertEquals(1, $sut->getMetaFields()->count());
        $result = $sut->getMetaField('foo');
        self::assertSame($result, $meta);
        self::assertEquals('test', $result->getType());

        $meta2 = new ProjectMeta();
        $meta2->setName('foo')->setValue('bar')->setType('test2');
        self::assertInstanceOf(Project::class, $sut->setMetaField($meta2));
        self::assertEquals(1, $sut->getMetaFields()->count());
        self::assertCount(0, $sut->getVisibleMetaFields());

        $result = $sut->getMetaField('foo');
        self::assertSame($result, $meta);
        self::assertEquals('test2', $result->getType());

        $sut->setMetaField((new ProjectMeta())->setName('blub')->setIsVisible(true));
        $sut->setMetaField((new ProjectMeta())->setName('blab')->setIsVisible(true));
        self::assertEquals(3, $sut->getMetaFields()->count());
        self::assertCount(2, $sut->getVisibleMetaFields());
    }

    public function testTeams()
    {
        $sut = new Project();
        $team = new Team();
        self::assertEmpty($sut->getTeams());
        self::assertEmpty($team->getProjects());

        $sut->addTeam($team);
        self::assertCount(1, $sut->getTeams());
        self::assertCount(1, $team->getProjects());
        self::assertSame($team, $sut->getTeams()[0]);
        self::assertSame($sut, $team->getProjects()[0]);

        // test remove unknown team doesn't do anything
        $sut->removeTeam(new Team());
        self::assertCount(1, $sut->getTeams());
        self::assertCount(1, $team->getProjects());

        $sut->removeTeam($team);
        self::assertCount(0, $sut->getTeams());
        self::assertCount(0, $team->getProjects());
    }

    public function testExportAnnotations()
    {
        $sut = new AnnotationExtractor(new AnnotationReader());

        $columns = $sut->extract(Project::class);

        self::assertIsArray($columns);

        $expected = [
            ['label.id', 'integer'],
            ['label.name', 'string'],
            ['label.customer', 'string'],
            ['label.orderNumber', 'string'],
            ['label.orderDate', 'datetime'],
            ['label.project_start', 'datetime'],
            ['label.project_end', 'datetime'],
            ['label.color', 'string'],
            ['label.visible', 'boolean'],
            ['label.comment', 'string'],
        ];

        self::assertCount(\count($expected), $columns);

        foreach ($columns as $column) {
            self::assertInstanceOf(ColumnDefinition::class, $column);
        }

        $i = 0;

        foreach ($expected as $item) {
            $column = $columns[$i++];
            self::assertEquals($item[0], $column->getLabel());
            self::assertEquals($item[1], $column->getType());
        }
    }
}
