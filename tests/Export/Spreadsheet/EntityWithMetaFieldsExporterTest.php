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
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Export\Spreadsheet\EntityWithMetaFieldsExporter
 */
class EntityWithMetaFieldsExporterTest extends TestCase
{
    public function testExport()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('dispatch')->willReturnCallback(function (ProjectMetaDisplayEvent $event) {
            $event->addField((new ProjectMeta())->setName('foo meta')->setIsVisible(true));
            $event->addField((new ProjectMeta())->setName('hidden meta')->setIsVisible(false));
            $event->addField((new ProjectMeta())->setName('bar meta')->setIsVisible(true));
        });

        $spreadsheetExporter = new SpreadsheetExporter($this->createMock(TranslatorInterface::class));
        $annotationExtractor = new AnnotationExtractor(new AnnotationReader());
        $metaFieldExtractor = new MetaFieldExtractor($dispatcher);

        $project = new Project();
        $project->setName('test project');
        $project->setCustomer((new Customer())->setName('A customer'));
        $project->setComment('Lorem Ipsum');
        $project->setOrderNumber('1234567890');
        $project->setBudget(123456.7890);
        $project->setTimeBudget(1234567890);
        $project->setColor('#ababab');
        $project->setVisible(false);
        $project->setMetaField((new ProjectMeta())->setName('foo meta')->setValue('some magic')->setIsVisible(true));
        $project->setMetaField((new ProjectMeta())->setName('hidden meta')->setValue('will not be seen')->setIsVisible(false));
        $project->setMetaField((new ProjectMeta())->setName('bar meta')->setValue('is happening')->setIsVisible(true));

        $sut = new EntityWithMetaFieldsExporter($spreadsheetExporter, $annotationExtractor, $metaFieldExtractor);
        $spreadsheet = $sut->export(Project::class, [$project], new ProjectMetaDisplayEvent(new ProjectQuery(), ProjectMetaDisplayEvent::EXPORT));
        $worksheet = $spreadsheet->getActiveSheet();

        self::assertNull($worksheet->getCellByColumnAndRow(1, 2, false)->getValue());
        self::assertEquals('test project', $worksheet->getCellByColumnAndRow(2, 2, false)->getValue());
        self::assertEquals('A customer', $worksheet->getCellByColumnAndRow(3, 2, false)->getValue());
        self::assertEquals(1234567890, $worksheet->getCellByColumnAndRow(4, 2, false)->getValue());
        self::assertEquals('', $worksheet->getCellByColumnAndRow(5, 2, false)->getValue());
        self::assertEquals('', $worksheet->getCellByColumnAndRow(6, 2, false)->getValue());
        self::assertEquals('', $worksheet->getCellByColumnAndRow(7, 2, false)->getValue());
        self::assertEquals('#ababab', $worksheet->getCellByColumnAndRow(8, 2, false)->getValue());
        self::assertFalse($worksheet->getCellByColumnAndRow(9, 2, false)->getValue());
        self::assertEquals('Lorem Ipsum', $worksheet->getCellByColumnAndRow(10, 2, false)->getValue());
        self::assertEquals('some magic', $worksheet->getCellByColumnAndRow(11, 2, false)->getValue());
        self::assertEquals('is happening', $worksheet->getCellByColumnAndRow(12, 2, false)->getValue());
    }
}
