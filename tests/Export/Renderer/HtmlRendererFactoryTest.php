<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Export\Renderer\HtmlRenderer;
use App\Export\Renderer\HtmlRendererFactory;
use App\Repository\ProjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

/**
 * @covers \App\Export\Renderer\HtmlRendererFactory
 */
class HtmlRendererFactoryTest extends TestCase
{
    public function testCreate()
    {
        $sut = new HtmlRendererFactory(
            $this->createMock(Environment::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(ProjectRepository::class)
        );

        $renderer = $sut->create('foo', 'bar.html.twig');

        self::assertInstanceOf(HtmlRenderer::class, $renderer);
        self::assertEquals('foo', $renderer->getId());
    }
}
