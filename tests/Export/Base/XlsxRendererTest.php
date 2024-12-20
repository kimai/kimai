<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Base;

use App\Entity\User;
use App\Export\Base\SpreadsheetRenderer;
use App\Export\Base\XlsxRenderer;
use App\Tests\Export\Renderer\AbstractRendererTest;
use App\Tests\Export\Renderer\MetaFieldColumnSubscriber;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Export\Base\XlsxRenderer
 * @covers \App\Export\Base\RendererTrait
 * @group integration
 */
class XlsxRendererTest extends AbstractRendererTest
{
    protected function getAbstractRenderer(): XlsxRenderer
    {
        $security = $this->createMock(Security::class);
        $security->expects($this->any())->method('getUser')->willReturn(new User());
        $security->expects($this->any())->method('isGranted')->willReturn(true);

        $translator = $this->createMock(TranslatorInterface::class);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new MetaFieldColumnSubscriber());

        return new XlsxRenderer(new SpreadsheetRenderer($translator, $dispatcher, $security));
    }

    public function testConfiguration(): void
    {
        $sut = $this->getAbstractRenderer();

        $this->assertEquals('xlsx', $sut->getId());
        $this->assertEquals('xlsx', $sut->getTitle());
    }

    public function testRender(): void
    {
        $sut = $this->getAbstractRenderer();

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
