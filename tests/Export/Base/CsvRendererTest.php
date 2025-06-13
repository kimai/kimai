<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Base;

use App\Entity\User;
use App\Export\Base\CsvRenderer;
use App\Export\Base\SpreadsheetRenderer;
use App\Tests\Export\Renderer\AbstractRendererTestCase;
use App\Tests\Mocks\MetaFieldColumnSubscriberMock;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Export\Base\CsvRenderer
 * @covers \App\Export\Base\SpreadsheetRenderer
 * @covers \App\Export\Base\RendererTrait
 * @covers \App\Export\Package\SpoutSpreadsheet
 * @group integration
 */
class CsvRendererTest extends AbstractRendererTestCase
{
    protected function getAbstractRenderer(bool $exportDecimal = false): CsvRenderer
    {
        $user = $this->createMock(User::class);
        $user->expects($this->any())->method('isExportDecimal')->willReturn($exportDecimal);

        $security = $this->createMock(Security::class);
        $security->expects($this->any())->method('getUser')->willReturn($user);
        $security->expects($this->any())->method('isGranted')->willReturn(true);

        $translator = $this->getContainer()->get(TranslatorInterface::class);
        self::assertInstanceOf(TranslatorInterface::class, $translator);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new MetaFieldColumnSubscriberMock());

        return new CsvRenderer(new SpreadsheetRenderer($dispatcher, $security, $this->createMock(LoggerInterface::class)), $translator);
    }

    public function testConfiguration(): void
    {
        $sut = $this->getAbstractRenderer();

        self::assertEquals('csv', $sut->getId());
        self::assertEquals('default', $sut->getTitle());

        $sut->setTitle('foo-bar');
        self::assertEquals('foo-bar', $sut->getTitle());

        $sut->setId('bar-id');
        self::assertEquals('bar-id', $sut->getId());
    }

    public static function getTestModel(): array
    {
        $en = [
            'Date', 'From', 'To', 'Duration', 'Currency', 'Price', 'Internal price', 'Hourly price', 'Fixed price', 'Name',
            'User', 'Staff number', 'Customer', 'Project', 'Activity', 'Description', 'Billable', 'Tags',
            'Type', 'category', 'Account', 'Project number', 'VAT-ID', 'Order number',
            'Working place', 'Working place', 'Working place', 'Working place', 'Working place', 'Working place', 'mypref',
        ];
        $de = [
            'Datum', 'Von', 'Bis', 'Dauer', 'Währung', 'Preis', 'Interner Preis', 'Preis pro Stunde', 'Festpreis', 'Name',
            'Benutzer', 'Personalnummer', 'Kunde', 'Projekt', 'Tätigkeit', 'Beschreibung', 'Abrechenbar', 'Schlagworte',
            'Typ', 'category', 'Kundennummer', 'Projektnummer', 'Umsatzsteuer-ID', 'Bestellnummer',
            'Working place', 'Working place', 'Working place', 'Working place', 'Working place', 'Working place', 'mypref',
        ];

        return [
            ['400', '2437.12', '1947.99', 7, 6, 1, 2, 2, false, null, $en],
            ['400', '2437.12', '1947.99', 7, 6, 1, 2, 2, true, 'de', $de]
        ];
    }

    /**
     * @dataProvider getTestModel
     */
    public function testRender(string $totalDuration, string $totalRate, string $expectedRate, int $expectedRows, int $expectedDescriptions, int $expectedUser1, int $expectedUser2, int $expectedUser3, bool $exportDecimal, ?string $locale, array $header): void
    {
        $sut = $this->getAbstractRenderer($exportDecimal);
        $sut->setLocale($locale);

        /** @var BinaryFileResponse $response */
        $response = $this->render($sut);

        $file = $response->getFile();
        $prefix = date('Ymd');
        self::assertEquals('text/csv', $response->headers->get('Content-Type'));
        self::assertEquals('attachment; filename=' . $prefix . '-Customer_Name-project_name.csv', $response->headers->get('Content-Disposition'));

        self::assertTrue(file_exists($file->getRealPath()));
        $content = file_get_contents($file->getRealPath());
        self::assertIsString($content);

        self::assertStringContainsString($expectedRate, $content);
        self::assertEquals($expectedRows, substr_count($content, PHP_EOL));
        self::assertEquals($expectedDescriptions, substr_count($content, '"activity description"'));
        self::assertEquals($expectedUser1, substr_count($content, ',kevin,'));
        self::assertEquals($expectedUser3, substr_count($content, ',hello-world,'));
        self::assertEquals($expectedUser2, substr_count($content, ',foo-bar,'));

        ob_start();
        $response->sendContent();
        $content2 = ob_get_clean();
        self::assertIsString($content2);

        self::assertEquals($content, $content2);
        self::assertFalse(file_exists($file->getRealPath()));

        $all = [];
        $rows = str_getcsv($content2, PHP_EOL);
        foreach ($rows as $row) {
            self::assertIsString($row);
            $all[] = str_getcsv($row);
        }

        self::assertEquals($header, $all[0]);

        $expected = [
            '2019-06-16',
            '12:00',
            '12:06',
            ($exportDecimal ? '0.11' : '0:06'),
            //'0.11',
            'EUR',
            '0',
            '0',
            '0',
            '84',
            'Kevin',
            'kevin',
            '',
            'Customer Name',
            'project name',
            'activity description',
            '',
            '1',
            'foo, bar',
            'timesheet',
            'work',
            'A-0123456789',
            '',
            'DE-9876543210',
            'ORDER-123',
            'meta-bar',
            'meta-bar2',
            'customer-bar',
            '',
            'project-foo2',
            'activity-bar',
            '',
        ];

        $expected2 = [
            '2019-06-16',
            '12:00',
            '12:06',
            ($exportDecimal ? '0.11' : '0:06'),
            //'0.11',
            'EUR',
            '0',
            '0',
            '0',
            '-100.92',
            'niveK',
            'nivek',
            '',
            'Customer Name',
            'project name',
            'activity description',
            '',
            '1',
            '',
            'timesheet',
            'work',
            'A-0123456789',
            '',
            'DE-9876543210',
            'ORDER-123',
            '',
            '',
            'customer-bar',
            '',
            'project-foo2',
            'activity-bar',
            '',
        ];

        self::assertEquals(7, \count($all));
        self::assertEquals($expected, $all[5]);
        self::assertEquals($expected2, $all[6]);
        self::assertEquals(\count($expected), \count($all[0]));
        self::assertEquals('foo', $all[4][17]);
    }
}
