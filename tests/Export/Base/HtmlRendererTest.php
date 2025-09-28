<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Base;

use App\Activity\ActivityStatisticService;
use App\Export\Base\HtmlRenderer;
use App\Export\Base\RendererTrait;
use App\Project\ProjectStatisticService;
use App\Tests\Export\Renderer\AbstractRendererTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

#[CoversClass(RendererTrait::class)]
#[CoversClass(HtmlRenderer::class)]
#[Group('integration')]
class HtmlRendererTest extends AbstractRendererTestCase
{
    protected function getAbstractRenderer(): HtmlRenderer
    {
        return new HtmlRenderer(
            $this->createMock(Environment::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(ProjectStatisticService::class),
            $this->createMock(ActivityStatisticService::class),
            'foo',
            'bar',
            'export/print.html.twig'
        );
    }

    public function testConfiguration(): void
    {
        $sut = $this->getAbstractRenderer();

        self::assertEquals('foo', $sut->getId());
        self::assertEquals('bar', $sut->getTitle());
        self::assertEquals('html', $sut->getType());
        self::assertFalse($sut->isInternal());
    }

    /**
     * @group legacy
     */
    public function testLegacy(): void
    {
        $sut = $this->getAbstractRenderer();

        $sut->setTemplate('some'); // @phpstan-ignore method.deprecated
        $sut->setId('xxxxxx'); // @phpstan-ignore method.deprecated
        self::assertEquals('xxxxxx', $sut->getId());
    }

    public function testRender(): void
    {
        $sut = $this->getAbstractRenderer();

        $response = $this->render($sut);
        self::assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        self::assertIsString($content);
    }
}
