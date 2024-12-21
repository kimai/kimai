<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Timesheet;

use App\Export\Timesheet\CsvRenderer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @covers \App\Export\Base\CsvRenderer
 * @covers \App\Export\Base\AbstractSpreadsheetRenderer
 * @covers \App\Export\Base\RendererTrait
 * @covers \App\Export\Timesheet\CsvRenderer
 * @group integration
 */
class CsvRendererTest extends AbstractRendererTestCase
{
    public function testConfiguration(): void
    {
        $sut = $this->getAbstractRenderer(CsvRenderer::class);

        self::assertEquals('csv', $sut->getId());
        self::assertEquals('csv', $sut->getTitle());
    }

    public static function getTestModel()
    {
        return [
            ['400', '2437.12', ' EUR 1,947.99 ', 6, 5, 1, 2, 2]
        ];
    }

    /**
     * @dataProvider getTestModel
     */
    public function testRender($totalDuration, $totalRate, $expectedRate, $expectedRows, $expectedDescriptions, $expectedUser1, $expectedUser2, $expectedUser3): void
    {
        $sut = $this->getAbstractRenderer(CsvRenderer::class);

        /** @var BinaryFileResponse $response */
        $response = $this->render($sut);

        $file = $response->getFile();
        $prefix = date('Ymd');
        self::assertEquals('text/csv', $response->headers->get('Content-Type'));
        self::assertEquals('attachment; filename=' . $prefix . '-Customer_Name-project_name.csv', $response->headers->get('Content-Disposition'));

        self::assertTrue(file_exists($file->getRealPath()));
        $content = file_get_contents($file->getRealPath());

        self::assertStringContainsString('"' . $expectedRate . '"', $content);
        self::assertEquals($expectedRows, substr_count($content, PHP_EOL));
        self::assertEquals($expectedDescriptions, substr_count($content, 'activity description'));
        self::assertEquals($expectedUser1, substr_count($content, ',"kevin",'));
        self::assertEquals($expectedUser3, substr_count($content, ',"hello-world",'));
        self::assertEquals($expectedUser2, substr_count($content, ',"foo-bar",'));

        ob_start();
        $response->sendContent();
        $content2 = ob_get_clean();

        self::assertEquals($content, $content2);
        self::assertFalse(file_exists($file->getRealPath()));

        $all = [];
        $rows = str_getcsv($content2, PHP_EOL);
        foreach ($rows as $row) {
            $all[] = str_getcsv($row);
        }

        $expected = [
            '2019-06-16',
            '12:00',
            '12:06',
            '400',
            '0',
            '',
            'kevin',
            'kevin',
            '',
            'Customer Name',
            'project name',
            'activity description',
            '',
            '',
            '',
            'foo,bar',
            '',
            ' EUR 84.00 ',
            'meta-bar',
            'meta-bar2',
            'customer-bar',
            '',
            'project-foo2',
            'activity-bar',
            'timesheet',
            'work',
            'A-0123456789',
            'DE-9876543210',
            'ORDER-123',
        ];

        self::assertEquals(6, \count($all));
        self::assertEquals($expected, $all[5]);
        self::assertEquals(\count($expected), \count($all[0]));
        self::assertEquals('foo', $all[4][15]);
    }
}
