<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks\Export;

use App\Export\Renderer\PdfRendererFactory;
use App\Pdf\HtmlToPdfConverter;
use App\Project\ProjectStatisticService;
use App\Tests\Mocks\AbstractMockFactory;
use Twig\Environment;

class PdfRendererFactoryMock extends AbstractMockFactory
{
    public function create(): PdfRendererFactory
    {
        return new PdfRendererFactory(
            $this->createMock(Environment::class),
            $this->createMock(HtmlToPdfConverter::class),
            $this->createMock(ProjectStatisticService::class),
        );
    }
}
