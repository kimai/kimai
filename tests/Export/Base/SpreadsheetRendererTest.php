<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Base;

use App\Entity\ExportableItem;
use App\Export\Base\SpreadsheetRenderer;
use App\Export\Package\SpreadsheetPackage;
use App\Export\Template;
use App\Repository\Query\TimesheetQuery;
use App\Tests\Export\Package\MemoryPackage;
use App\Tests\Export\Renderer\AbstractRendererTestCase;
use App\Tests\Mocks\MetaFieldColumnSubscriberMock;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Export\Base\SpreadsheetRenderer
 * @group integration
 */
class SpreadsheetRendererTest extends AbstractRendererTestCase
{
    public function testWriteSpreadsheetCreatesSpreadsheetWithCorrectHeaders(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $security = $this->createMock(Security::class);
        $spreadsheetPackage = $this->createMock(SpreadsheetPackage::class);
        $spreadsheetPackage->expects(self::once())->method('setColumns');

        $renderer = new SpreadsheetRenderer($dispatcher, $security, $this->createMock(LoggerInterface::class));
        $renderer->writeSpreadsheet($spreadsheetPackage, [], new TimesheetQuery());
    }

    public function testWriteSpreadsheetAddsRowsForExportItems(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $security = $this->createMock(Security::class);
        $spreadsheetPackage = $this->createMock(SpreadsheetPackage::class);
        $spreadsheetPackage->expects(self::exactly(2))->method('addRow');
        $spreadsheetPackage->expects(self::once())->method('save');

        $exportItem = $this->createMock(ExportableItem::class);
        $exportItem->method('getBegin')->willReturn(new \DateTime());
        $exportItem->method('getEnd')->willReturn(new \DateTime());
        $exportItem->method('getDuration')->willReturn(3600);
        $exportItem->method('getRate')->willReturn(100.0);
        $exportItem->method('getInternalRate')->willReturn(80.0);
        $exportItem->method('getHourlyRate')->willReturn(50.0);
        $exportItem->method('getFixedRate')->willReturn(200.0);
        $exportItem->method('getUser')->willReturn(null);
        $exportItem->method('getProject')->willReturn(null);
        $exportItem->method('getActivity')->willReturn(null);
        $exportItem->method('getDescription')->willReturn('Test description');
        $exportItem->method('isBillable')->willReturn(true);
        $exportItem->method('getTagsAsArray')->willReturn(['tag1', 'tag2']);
        $exportItem->method('getType')->willReturn('type');
        $exportItem->method('getCategory')->willReturn('category');

        $renderer = new SpreadsheetRenderer($dispatcher, $security, $this->createMock(LoggerInterface::class));
        $renderer->writeSpreadsheet($spreadsheetPackage, [$exportItem], new TimesheetQuery());
    }

    public function testWriteSpreadsheetAddsTotalRowWhenMoreThanOneRow(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $security = $this->createMock(Security::class);
        $spreadsheetPackage = $this->createMock(SpreadsheetPackage::class);
        $spreadsheetPackage->expects(self::exactly(3))->method('addRow');

        $exportItem = $this->createMock(ExportableItem::class);
        $exportItem->method('getBegin')->willReturn(new \DateTime());
        $exportItem->method('getEnd')->willReturn(new \DateTime());
        $exportItem->method('getDuration')->willReturn(3600);
        $exportItem->method('getRate')->willReturn(100.0);
        $exportItem->method('getInternalRate')->willReturn(80.0);
        $exportItem->method('getHourlyRate')->willReturn(50.0);
        $exportItem->method('getFixedRate')->willReturn(200.0);
        $exportItem->method('getUser')->willReturn(null);
        $exportItem->method('getProject')->willReturn(null);
        $exportItem->method('getActivity')->willReturn(null);
        $exportItem->method('getDescription')->willReturn('Test description');
        $exportItem->method('isBillable')->willReturn(true);
        $exportItem->method('getTagsAsArray')->willReturn(['tag1', 'tag2']);
        $exportItem->method('getType')->willReturn('type');
        $exportItem->method('getCategory')->willReturn('category');

        $renderer = new SpreadsheetRenderer($dispatcher, $security);
        $renderer->writeSpreadsheet($spreadsheetPackage, [$exportItem, $exportItem], new TimesheetQuery());
    }

    public static function getTestData(): iterable
    {
        yield [null, [
            'date' => 'date',
            'begin' => 'begin',
            'end' => 'end',
            'duration' => 'duration',
            'currency' => 'currency',
            'rate' => 'rate',
            'internalRate' => 'internalRate',
            'hourlyRate' => 'hourlyRate',
            'fixedRate' => 'fixedRate',
            'alias' => 'alias',
            'username' => 'username',
            'account_number' => 'account_number',
            'customer' => 'customer',
            'project' => 'project',
            'activity' => 'activity',
            'description' => 'description',
            'billable' => 'billable',
            'tags' => 'tags',
            'type' => 'type',
            'category' => 'category',
            'number' => 'number',
            'project_number' => 'project_number',
            'vat_id' => 'vat_id',
            'orderNumber' => 'orderNumber',
            'timesheet.meta.foo' => 'Working place',
            'timesheet.meta.foo2' => 'Working place',
            'customer.meta.customer-foo' => 'Working place',
            'project.meta.project-foo' => 'Working place',
            'project.meta.project-foo2' => 'Working place',
            'activity.meta.activity-foo' => 'Working place',
            'user.meta.mypref' => 'mypref',
        ]];

        $template = new Template('test', 'Testing');
        $template->setLocale('de');
        $template->setColumns(['date', 'user.name', 'duration_decimal', 'customer.name', 'exported', 'user.meta.mypref']);

        yield [$template, [
            'date' => 'date',
            'duration' => 'duration',
            'username' => 'username',
            'customer' => 'customer',
            'exported' => 'exported',
            'user.meta.mypref' => 'mypref',
        ]];
    }

    /**
     * @dataProvider getTestData
     */
    public function testWriteSpreadsheetCsv(?Template $template, array $expectedColumns): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new MetaFieldColumnSubscriberMock());

        $security = $this->createMock(Security::class);
        $spreadsheetPackage = new MemoryPackage();

        $exportItem = $this->createMock(ExportableItem::class);
        $exportItem->method('getBegin')->willReturn(new \DateTime());
        $exportItem->method('getEnd')->willReturn(new \DateTime());
        $exportItem->method('getDuration')->willReturn(3600);
        $exportItem->method('getRate')->willReturn(100.0);
        $exportItem->method('getInternalRate')->willReturn(80.0);
        $exportItem->method('getHourlyRate')->willReturn(50.0);
        $exportItem->method('getFixedRate')->willReturn(200.0);
        $exportItem->method('getUser')->willReturn(null);
        $exportItem->method('getProject')->willReturn(null);
        $exportItem->method('getActivity')->willReturn(null);
        $exportItem->method('getDescription')->willReturn('Test description');
        $exportItem->method('isBillable')->willReturn(true);
        $exportItem->method('getTagsAsArray')->willReturn(['tag1', 'tag2']);
        $exportItem->method('getType')->willReturn('type');
        $exportItem->method('getCategory')->willReturn('category');

        $renderer = new SpreadsheetRenderer($dispatcher, $security, $this->createMock(LoggerInterface::class));
        $renderer->setTemplate($template);
        $renderer->writeSpreadsheet($spreadsheetPackage, [$exportItem], new TimesheetQuery());

        $columnNames = [];
        foreach ($spreadsheetPackage->getColumns() as $column) {
            $columnNames[$column->getName()] = $column->getHeader();
        }

        self::assertEquals(null, $spreadsheetPackage->getFilename());
        self::assertEquals($expectedColumns, $columnNames);
        self::assertCount(2, $spreadsheetPackage->getRows());
    }
}
