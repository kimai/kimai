<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet;

use App\Entity\Customer;
use App\Entity\CustomerMeta;
use App\Event\CustomerMetaDisplayEvent;
use App\Export\Spreadsheet\EntityWithMetaFieldsExporter;
use App\Export\Spreadsheet\Extractor\AnnotationExtractor;
use App\Export\Spreadsheet\Extractor\MetaFieldExtractor;
use App\Export\Spreadsheet\SpreadsheetExporter;
use App\Repository\Query\CustomerQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(EntityWithMetaFieldsExporter::class)]
class CustomerExporterTest extends TestCase
{
    public function testExport(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('dispatch')->willReturnCallback(function (CustomerMetaDisplayEvent $event) {
            $event->addField((new CustomerMeta())->setName('foo meta')->setIsVisible(true));
            $event->addField((new CustomerMeta())->setName('hidden meta')->setIsVisible(false));
            $event->addField((new CustomerMeta())->setName('bar meta')->setIsVisible(true));

            return $event;
        });

        $spreadsheetExporter = new SpreadsheetExporter($this->createMock(TranslatorInterface::class));
        $annotationExtractor = new AnnotationExtractor();
        $metaFieldExtractor = new MetaFieldExtractor($dispatcher);

        $customer = new Customer('test customer');
        $customer->setCompany('Acme Foo');
        $customer->setVatId('DE0123456789');
        $customer->setComment('Lorem Ipsum');
        $customer->setBudget(123456.7890);
        $customer->setTimeBudget(1234567890);
        $customer->setColor('#ababab');
        $customer->setVisible(false);
        $customer->setNumber('CU-0815');
        $customer->setMetaField((new CustomerMeta())->setName('foo meta')->setValue('some magic')->setIsVisible(true));
        $customer->setMetaField((new CustomerMeta())->setName('hidden meta')->setValue('will not be seen')->setIsVisible(false));
        $customer->setMetaField((new CustomerMeta())->setName('bar meta')->setValue('is happening')->setIsVisible(true));
        $customer->setCountry('AT');
        $customer->setAddressLine1('1234 Main Road');
        $customer->setAddressLine2('Third Floor');
        $customer->setAddressLine3('Golden Office Tower');
        $customer->setCity('Acme City');
        $customer->setPostCode('MT 543.6');
        $customer->setBuyerReference('BR-0987654321');

        $sut = new EntityWithMetaFieldsExporter($spreadsheetExporter, $annotationExtractor, $metaFieldExtractor);
        $spreadsheet = $sut->export(Customer::class, [$customer], new CustomerMetaDisplayEvent(new CustomerQuery(), CustomerMetaDisplayEvent::EXPORT));
        $worksheet = $spreadsheet->getActiveSheet();

        $i = 0;

        self::assertNull($worksheet->getCell([++$i, 2])->getValue()); // id
        self::assertEquals('test customer', $worksheet->getCell([++$i, 2])->getValue()); // name
        self::assertEquals('Acme Foo', $worksheet->getCell([++$i, 2])->getValue()); // company
        self::assertEquals('CU-0815', $worksheet->getCell([++$i, 2])->getValue()); // number
        self::assertEquals('DE0123456789', $worksheet->getCell([++$i, 2])->getValue()); // vatId
        self::assertEquals(null, $worksheet->getCell([++$i, 2])->getValue()); // address
        self::assertEquals(null, $worksheet->getCell([++$i, 2])->getValue()); // contact
        self::assertEquals(null, $worksheet->getCell([++$i, 2])->getValue()); // email
        self::assertEquals(null, $worksheet->getCell([++$i, 2])->getValue()); // phone
        self::assertEquals(null, $worksheet->getCell([++$i, 2])->getValue()); // mobile
        self::assertEquals(null, $worksheet->getCell([++$i, 2])->getValue()); // fax
        self::assertEquals(null, $worksheet->getCell([++$i, 2])->getValue()); // homepage
        self::assertEquals('1234 Main Road', $worksheet->getCell([++$i, 2])->getValue()); // address_line1
        self::assertEquals('Third Floor', $worksheet->getCell([++$i, 2])->getValue()); // address_line2
        self::assertEquals('Golden Office Tower', $worksheet->getCell([++$i, 2])->getValue()); // address_line3
        self::assertEquals('MT 543.6', $worksheet->getCell([++$i, 2])->getValue()); // postcode
        self::assertEquals('Acme City', $worksheet->getCell([++$i, 2])->getValue()); // city
        self::assertEquals('AT', $worksheet->getCell([++$i, 2])->getValue()); // country
        self::assertEquals('EUR', $worksheet->getCell([++$i, 2])->getValue()); // currency
        self::assertEquals(null, $worksheet->getCell([++$i, 2])->getValue()); // timezone
        self::assertEquals('123456.789', $worksheet->getCell([++$i, 2])->getValue()); // budget
        self::assertEquals('=1234567890/86400', $worksheet->getCell([++$i, 2])->getValue()); // timeBudget
        self::assertEquals(null, $worksheet->getCell([++$i, 2])->getValue()); // budgetType
        self::assertEquals('#ababab', $worksheet->getCell([++$i, 2])->getValue()); // color
        self::assertFalse($worksheet->getCell([++$i, 2])->getValue()); // visible
        self::assertEquals('Lorem Ipsum', $worksheet->getCell([++$i, 2])->getValue()); // comment
        self::assertTrue($worksheet->getCell([++$i, 2])->getValue()); // billable
        self::assertEquals('BR-0987654321', $worksheet->getCell([++$i, 2])->getValue()); // buyer reference
        self::assertEquals('some magic', $worksheet->getCell([++$i, 2])->getValue());
        self::assertEquals('is happening', $worksheet->getCell([++$i, 2])->getValue());
    }
}
