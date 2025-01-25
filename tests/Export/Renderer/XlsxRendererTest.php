<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Export\Renderer\XlsxRenderer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @covers \App\Export\Base\XlsxRenderer
 * @covers \App\Export\Base\AbstractSpreadsheetRenderer
 * @covers \App\Export\Base\RendererTrait
 * @covers \App\Export\Renderer\XlsxRenderer
 * @group integration
 */
class XlsxRendererTest extends AbstractRendererTest
{
    public function testConfiguration(): void
    {
        $sut = $this->getAbstractRenderer(XlsxRenderer::class);

        $this->assertEquals('xlsx', $sut->getId());
        $this->assertEquals('xlsx', $sut->getTitle());
    }

    public function testRender(): void
    {
        $sut = $this->getAbstractRenderer(XlsxRenderer::class);

        /** @var BinaryFileResponse $response */
        $response = $this->render($sut);

        $file = $response->getFile();
        $prefix = date('Ymd');
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename=' . $prefix . '-Customer_Name-project_name.xlsx', $response->headers->get('Content-Disposition'));

        $this->assertTrue(file_exists($file->getRealPath()));

        ob_start();
        $response->sendContent();
        $content2 = ob_get_clean();
        $this->assertNotEmpty($content2);

        $this->assertFalse(file_exists($file->getRealPath()));
    }
}
