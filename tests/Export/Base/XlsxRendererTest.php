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
use App\Tests\Export\Renderer\AbstractRendererTestCase;
use App\Tests\Export\Renderer\MetaFieldColumnSubscriber;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Export\Base\XlsxRenderer
 * @covers \App\Export\Base\SpreadsheetRenderer
 * @covers \App\Export\Base\RendererTrait
 * @covers \App\Export\Package\SpoutSpreadsheet
 * @group integration
 */
class XlsxRendererTest extends AbstractRendererTestCase
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

        self::assertEquals('xlsx', $sut->getId());
        self::assertEquals('xlsx', $sut->getTitle());
    }

    public function testRender(): void
    {
        $sut = $this->getAbstractRenderer();

        /** @var BinaryFileResponse $response */
        $response = $this->render($sut);

        $file = $response->getFile();
        $prefix = date('Ymd');
        self::assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        self::assertEquals('attachment; filename=' . $prefix . '-Customer_Name-project_name.xlsx', $response->headers->get('Content-Disposition'));

        self::assertTrue(file_exists($file->getRealPath()));

        ob_start();
        $response->sendContent();
        $content2 = ob_get_clean();
        self::assertNotEmpty($content2);

        self::assertFalse(file_exists($file->getRealPath()));
    }
}
