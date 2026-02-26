<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Base;

use App\Entity\User;
use App\Export\Base\AbstractSpreadsheetRenderer;
use App\Export\Base\XlsxRenderer;
use App\Export\ColumnConverter;
use App\Export\Package\SpoutSpreadsheet;
use App\Export\Renderer\XlsxRendererFactory;
use App\Tests\Export\Renderer\AbstractRendererTestCase;
use App\Tests\Mocks\MetaFieldColumnSubscriberMock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(AbstractSpreadsheetRenderer::class)]
#[CoversClass(SpoutSpreadsheet::class)]
#[CoversClass(XlsxRenderer::class)]
#[Group('integration')]
class XlsxRendererTest extends AbstractRendererTestCase
{
    protected function getAbstractRenderer(): XlsxRenderer
    {
        $security = $this->createMock(Security::class);
        $security->expects($this->any())->method('getUser')->willReturn(new User());
        $security->expects($this->any())->method('isGranted')->willReturn(true);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new MetaFieldColumnSubscriberMock());

        $converter = new ColumnConverter($dispatcher, $security);
        $factory = new XlsxRendererFactory($converter, $dispatcher, $translator);

        return $factory->createDefault();
    }

    public function testConfigurationFromTemplate(): void
    {
        $sut = $this->getAbstractRenderer();

        self::assertEquals('xlsx', $sut->getType());
        self::assertEquals('xlsx', $sut->getId());
        self::assertEquals('xlsx', $sut->getTitle());
        self::assertFalse($sut->isInternal());
        $sut->setInternal(true);
        self::assertTrue($sut->isInternal());
    }

    public function testRender(): void
    {
        $sut = $this->getAbstractRenderer();

        $response = $this->render($sut);
        self::assertInstanceOf(BinaryFileResponse::class, $response);

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
