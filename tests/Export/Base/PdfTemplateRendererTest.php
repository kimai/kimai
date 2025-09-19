<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Base;

use App\Entity\User;
use App\Export\Base\PdfTemplateRenderer;
use App\Export\ColumnConverter;
use App\Export\DefaultTemplate;
use App\Pdf\HtmlToPdfConverter;
use App\Project\ProjectStatisticService;
use App\Tests\Export\Renderer\AbstractRendererTestCase;
use App\Tests\Mocks\MetaFieldColumnSubscriberMock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Translation\LocaleSwitcher;
use Twig\Environment;

/**
 * @covers \App\Export\Base\RendererTrait
 * @covers \App\Pdf\PdfRendererTrait
 */
#[CoversClass(PdfTemplateRenderer::class)]
#[Group('integration')]
class PdfTemplateRendererTest extends AbstractRendererTestCase
{
    protected function getAbstractRenderer(): PdfTemplateRenderer
    {
        $twig = $this->createMock(Environment::class);
        $htmlConverter = $this->createMock(HtmlToPdfConverter::class);
        $projectStatisticService = $this->createMock(ProjectStatisticService::class);

        $security = $this->createMock(Security::class);
        $security->expects($this->any())->method('getUser')->willReturn(new User());
        $security->expects($this->any())->method('isGranted')->willReturn(true);
        $security->expects($this->any())->method('isGranted')->willReturn(true);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new MetaFieldColumnSubscriberMock());

        $converter = new ColumnConverter($dispatcher, $security);

        $template = new DefaultTemplate($dispatcher, 'test', 'en', 'bar');

        return new PdfTemplateRenderer(
            $twig,
            $htmlConverter,
            $projectStatisticService,
            $converter,
            $this->createMock(LocaleSwitcher::class),
            $template
        );
    }

    public function testConfiguration(): void
    {
        $sut = $this->getAbstractRenderer();

        self::assertEquals('test', $sut->getId());
        self::assertEquals('bar', $sut->getTitle());
        self::assertEquals('pdf', $sut->getType());
        self::assertTrue($sut->isInternal());
    }
}
