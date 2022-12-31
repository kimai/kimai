<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Export\Renderer\CsvRenderer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @covers \App\Export\Base\CsvRenderer
 * @covers \App\Export\Base\AbstractSpreadsheetRenderer
 * @covers \App\Export\Base\RendererTrait
 * @covers \App\Export\Renderer\CsvRenderer
 * @group integration
 */
class CsvRendererTest extends AbstractRendererTest
{
    public function testConfiguration()
    {
        $sut = $this->getAbstractRenderer(CsvRenderer::class);

        $this->assertEquals('csv', $sut->getId());
        $this->assertEquals('csv', $sut->getTitle());
        $this->assertEquals('csv', $sut->getIcon());
    }

    public function getTestModel()
    {
        return [
            ['400', '2437.12', ' EUR 1,947.99 ', 7, 6, 1, 2, 2]
        ];
    }

    /**
     * @dataProvider getTestModel
     */
    public function testRender($totalDuration, $totalRate, $expectedRate, $expectedRows, $expectedDescriptions, $expectedUser1, $expectedUser2, $expectedUser3)
    {
        $sut = $this->getAbstractRenderer(CsvRenderer::class);

        /** @var BinaryFileResponse $response */
        $response = $this->render($sut);

        $file = $response->getFile();
        $prefix = date('Ymd');
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename=' . $prefix . '-Customer_Name-project_name.csv', $response->headers->get('Content-Disposition'));

        $this->assertTrue(file_exists($file->getRealPath()));
        $content = file_get_contents($file->getRealPath());

        $this->assertStringContainsString('"' . $expectedRate . '"', $content);
        $this->assertEquals($expectedRows, substr_count($content, PHP_EOL));
        $this->assertEquals($expectedDescriptions, substr_count($content, 'activity description'));
        $this->assertEquals($expectedUser1, substr_count($content, ',"kevin",'));
        $this->assertEquals($expectedUser3, substr_count($content, ',"hello-world",'));
        $this->assertEquals($expectedUser2, substr_count($content, ',"foo-bar",'));

        ob_start();
        $response->sendContent();
        $content2 = ob_get_clean();

        $this->assertEquals($content, $content2);
        $this->assertFalse(file_exists($file->getRealPath()));

        $all = [];
        $rows = str_getcsv($content2, PHP_EOL);
        foreach ($rows as $row) {
            $all[] = str_getcsv($row);
        }

        $expected = [
            0 => '2019-06-16',
            1 => '12:00',
            2 => '12:06',
            3 => '400',
            4 => '0',
            5 => '',
            6 => 'kevin',
            7 => 'kevin',
            8 => 'Customer Name',
            9 => 'project name',
            10 => 'activity description',
            11 => '',
            12 => '',
            13 => '',
            14 => 'foo,bar',
            15 => '',
            16 => ' EUR 84.00 ',
            17 => 'meta-bar',
            18 => 'meta-bar2',
            19 => 'customer-bar',
            20 => '',
            21 => 'project-foo2',
            22 => 'activity-bar',
            23 => 'timesheet',
            24 => 'work',
            25 => 'A-0123456789',
            26 => 'DE-9876543210',
            27 => 'ORDER-123',
        ];

        $expected2 = [
            0 => '2019-06-16',
            1 => '12:00',
            2 => '12:06',
            3 => '400',
            4 => '0',
            5 => '',
            6 => 'nivek',
            7 => 'nivek',
            8 => 'Customer Name',
            9 => 'project name',
            10 => 'activity description',
            11 => '',
            12 => '',
            13 => '',
            14 => '',
            15 => '',
            16 => ' EUR -100.92',
            17 => '',
            18 => '',
            19 => 'customer-bar',
            20 => '',
            21 => 'project-foo2',
            22 => 'activity-bar',
            23 => 'timesheet',
            24 => 'work',
            25 => 'A-0123456789',
            26 => 'DE-9876543210',
            27 => 'ORDER-123',
        ];

        self::assertEquals(7, \count($all));
        self::assertEquals($expected, $all[5]);
        self::assertEquals($expected2, $all[6]);
        self::assertEquals(\count($expected), \count($all[0]));
        self::assertEquals('foo', $all[4][14]);
    }
}
