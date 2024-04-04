<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet;

use App\Entity\Activity;
use App\Entity\ActivityMeta;
use App\Event\ActivityMetaDisplayEvent;
use App\Export\Spreadsheet\EntityWithMetaFieldsExporter;
use App\Export\Spreadsheet\Extractor\AnnotationExtractor;
use App\Export\Spreadsheet\Extractor\MetaFieldExtractor;
use App\Export\Spreadsheet\SpreadsheetExporter;
use App\Repository\Query\ActivityQuery;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Export\Spreadsheet\EntityWithMetaFieldsExporter
 */
class ActivityExporterTest extends TestCase
{
    public function testExport(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('dispatch')->willReturnCallback(function (ActivityMetaDisplayEvent $event) {
            $event->addField((new ActivityMeta())->setName('foo meta')->setIsVisible(true));
            $event->addField((new ActivityMeta())->setName('hidden meta')->setIsVisible(false));
            $event->addField((new ActivityMeta())->setName('bar meta')->setIsVisible(true));

            return $event;
        });

        $spreadsheetExporter = new SpreadsheetExporter($this->createMock(TranslatorInterface::class));
        $annotationExtractor = new AnnotationExtractor();
        $metaFieldExtractor = new MetaFieldExtractor($dispatcher);

        $activity = new Activity();
        $activity->setName('test activity');
        $activity->setComment('Lorem Ipsum');
        $activity->setBudget(123456.7890);
        $activity->setTimeBudget(1234567890);
        $activity->setColor('#ababab');
        $activity->setVisible(false);
        $activity->setNumber('AC-0815');
        $activity->setMetaField((new ActivityMeta())->setName('foo meta')->setValue('some magic')->setIsVisible(true));
        $activity->setMetaField((new ActivityMeta())->setName('hidden meta')->setValue('will not be seen')->setIsVisible(false));
        $activity->setMetaField((new ActivityMeta())->setName('bar meta')->setValue('is happening')->setIsVisible(true));

        $sut = new EntityWithMetaFieldsExporter($spreadsheetExporter, $annotationExtractor, $metaFieldExtractor);
        $spreadsheet = $sut->export(Activity::class, [$activity], new ActivityMetaDisplayEvent(new ActivityQuery(), ActivityMetaDisplayEvent::EXPORT));
        $worksheet = $spreadsheet->getActiveSheet();

        $i = 0;
        self::assertNull($worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('test activity', $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('', $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals(123456.7890, $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('=1234567890/86400', $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('', $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('#ababab', $worksheet->getCell([++$i, 2])->getValue());
        self::assertFalse($worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('Lorem Ipsum', $worksheet->getCell([++$i, 2])->getValue());
        self::assertTrue($worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('AC-0815', $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('some magic', $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('is happening', $worksheet->getCell([++$i, 2])->getValue());
    }
}
