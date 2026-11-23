<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks\Export;

use App\Export\ColumnConverter;
use App\Export\Renderer\PdfRendererFactory;
use App\Pdf\HtmlToPdfConverter;
use App\Project\ProjectStatisticService;
use App\Tests\Mocks\AbstractMockFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Translation\LocaleSwitcher;
use Twig\Environment;

class PdfRendererFactoryMock extends AbstractMockFactory
{
    public function create(?Environment $environment = null): PdfRendererFactory
    {
        $converter = new ColumnConverter(
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(Security::class),
            $this->createMock(LoggerInterface::class),
        );

        return new PdfRendererFactory(
            $environment ?? $this->createMock(Environment::class),
            $this->createMock(HtmlToPdfConverter::class),
            $this->createMock(ProjectStatisticService::class),
            $this->createMock(LocaleSwitcher::class),
            $converter
        );
    }
}
