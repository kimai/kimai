<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Export\Base\XlsxRenderer;
use App\Export\ColumnConverter;
use App\Export\Renderer\XlsxRendererFactory;
use App\Export\Template;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(XlsxRendererFactory::class)]
class XlsxRendererFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $converter = new ColumnConverter(
            $dispatcher,
            $this->createMock(Security::class),
            $this->createMock(LoggerInterface::class)
        );
        $sut = new XlsxRendererFactory(
            $converter,
            $dispatcher,
            $this->createMock(TranslatorInterface::class),
        );

        $template = new Template('foo-id', 'bar-title');
        $template->setLocale('it_IT');

        $renderer = $sut->create($template);

        self::assertInstanceOf(XlsxRenderer::class, $renderer);
        self::assertEquals('foo-id', $renderer->getId());
        self::assertEquals('bar-title', $renderer->getTitle());
        self::assertTrue($renderer->isInternal());
    }
}
