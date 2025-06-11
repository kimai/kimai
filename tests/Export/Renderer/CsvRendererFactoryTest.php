<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Export\Base\CsvRenderer;
use App\Export\Renderer\CsvRendererFactory;
use App\Export\Template;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Export\Renderer\CsvRendererFactory
 */
class CsvRendererFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $sut = new CsvRendererFactory(
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(Security::class),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(LoggerInterface::class)
        );

        $template = new Template('foo-id', 'bar-title');
        $template->setLocale('it_IT');

        $renderer = $sut->create($template);

        self::assertInstanceOf(CsvRenderer::class, $renderer);
        self::assertEquals('foo-id', $renderer->getId());
        self::assertEquals('bar-title', $renderer->getTitle());
    }
}
