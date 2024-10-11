<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet;

use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\ProjectMeta;
use App\Event\ProjectMetaDisplayEvent;
use App\Export\Spreadsheet\EntityWithMetaFieldsExporter;
use App\Export\Spreadsheet\Extractor\AnnotationExtractor;
use App\Export\Spreadsheet\Extractor\MetaFieldExtractor;
use App\Export\Spreadsheet\SpreadsheetExporter;
use App\Repository\Query\ProjectQuery;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Export\Spreadsheet\EntityWithMetaFieldsExporter
 */
class EntityWithMetaFieldsExporterTest extends TestCase
{
    public function testExport(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('dispatch')->willReturnCallback(function (ProjectMetaDisplayEvent $event) {
            $event->addField((new ProjectMeta())->setName('foo meta')->setIsVisible(true));
            $event->addField((new ProjectMeta())->setName('hidden meta')->setIsVisible(false));
            $event->addField((new ProjectMeta())->setName('bar meta')->setIsVisible(true));

            return $event;
        });

        $spreadsheetExporter = new SpreadsheetExporter($this->createMock(TranslatorInterface::class));
        $annotationExtractor = new AnnotationExtractor();
        $metaFieldExtractor = new MetaFieldExtractor($dispatcher);

        $project = new Project();
        $project->setName('test project');
        $project->setCustomer(new Customer('A customer'));
        $project->setComment('Lorem Ipsum');
        $project->setOrderNumber('1234567890');
        $project->setBudget(123456.7890);
        $project->setTimeBudget(1234567890);
        $project->setColor('#ababab');
        $project->setVisible(false);
        $project->setNumber('PRJ-0815');
        $project->setMetaField((new ProjectMeta())->setName('foo meta')->setValue('some magic')->setIsVisible(true));
        $project->setMetaField((new ProjectMeta())->setName('hidden meta')->setValue('will not be seen')->setIsVisible(false));
        $project->setMetaField((new ProjectMeta())->setName('bar meta')->setValue('is happening')->setIsVisible(true));

        $sut = new EntityWithMetaFieldsExporter($spreadsheetExporter, $annotationExtractor, $metaFieldExtractor);
        $spreadsheet = $sut->export(Project::class, [$project], new ProjectMetaDisplayEvent(new ProjectQuery(), ProjectMetaDisplayEvent::EXPORT));
        $worksheet = $spreadsheet->getActiveSheet();

        $i = 0;
        self::assertNull($worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('test project', $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('A customer', $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals(1234567890, $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('', $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('', $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('', $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals(123456.7890, $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('=1234567890/86400', $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('', $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('#ababab', $worksheet->getCell([++$i, 2])->getValue());
        self::assertFalse($worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('Lorem Ipsum', $worksheet->getCell([++$i, 2])->getValue());
        self::assertTrue($worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('PRJ-0815', $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('some magic', $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('is happening', $worksheet->getCell([++$i, 2])->getValue());
    }
}
