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
use App\Repository\Query\TimesheetQuery;
use App\Tests\Export\Renderer\AbstractRendererTestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Export\Base\SpreadsheetRenderer
 * @group integration
 */
class SpreadsheetRendererTest extends AbstractRendererTestCase
{
    public function testWriteSpreadsheetCreatesSpreadsheetWithCorrectHeaders(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $security = $this->createMock(Security::class);
        $spreadsheetPackage = $this->createMock(SpreadsheetPackage::class);
        $spreadsheetPackage->expects(self::once())->method('setHeader')->with([
            'date', 'begin', 'end', 'duration', 'currency', 'rate', 'internalRate', 'hourlyRate', 'fixedRate', 'username', 'account_number', 'customer', 'project', 'activity', 'description', 'billable', 'tags', 'type', 'category', 'number', 'project_number', 'vat_id', 'orderNumber'
        ]);

        $renderer = new SpreadsheetRenderer($translator, $dispatcher, $security);
        $renderer->writeSpreadsheet($spreadsheetPackage, [], new TimesheetQuery());
    }

    public function testWriteSpreadsheetAddsRowsForExportItems(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);
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

        $renderer = new SpreadsheetRenderer($translator, $dispatcher, $security);
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

        $renderer = new SpreadsheetRenderer($translator, $dispatcher, $security);
        $renderer->writeSpreadsheet($spreadsheetPackage, [$exportItem, $exportItem], new TimesheetQuery());
    }
}
