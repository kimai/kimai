<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Export\Base\XlsxRenderer;
use App\Export\Renderer\XlsxRendererFactory;
use App\Export\Template;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Export\Renderer\XlsxRendererFactory
 */
class XlsxRendererFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $sut = new XlsxRendererFactory(
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(Security::class),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(LoggerInterface::class)
        );

        $template = new Template('foo-id', 'bar-title');
        $template->setLocale('it_IT');

        $renderer = $sut->create($template);

        self::assertInstanceOf(XlsxRenderer::class, $renderer);
        self::assertEquals('foo-id', $renderer->getId());
        self::assertEquals('bar-title', $renderer->getTitle());
    }
}
