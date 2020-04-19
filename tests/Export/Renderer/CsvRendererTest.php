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
 * @covers \App\Export\Renderer\AbstractSpreadsheetRenderer
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
            ['400', '2437.12', ' EUR 1,947.99 ', 7, 5, 1, 2, 2]
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
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename=kimai-export.csv', $response->headers->get('Content-Disposition'));

        $this->assertTrue(file_exists($file->getRealPath()));
        $content = file_get_contents($file->getRealPath());

        $this->assertStringContainsString('"' . $totalDuration . '"', $content);
        $this->assertStringContainsString('"' . $totalRate . '"', $content);
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
            7 => 'Customer Name',
            8 => 'project name',
            9 => 'activity description',
            10 => '',
            11 => '',
            12 => 'foo,bar',
            13 => '',
            14 => ' EUR 84.00 ',
            15 => 'meta-bar',
            16 => 'meta-bar2',
            17 => 'customer-bar',
            18 => '',
            19 => 'project-foo2',
            20 => 'activity-bar',
        ];

        self::assertEquals(7, \count($all));
        self::assertEquals($expected, $all[5]);
        self::assertEquals(\count($expected), \count($all[0]));
        self::assertEquals('foo', $all[4][12]);
    }
}
