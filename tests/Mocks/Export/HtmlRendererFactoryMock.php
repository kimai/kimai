<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks\Export;

use App\Activity\ActivityStatisticService;
use App\Export\Renderer\HtmlRendererFactory;
use App\Project\ProjectStatisticService;
use App\Tests\Mocks\AbstractMockFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class HtmlRendererFactoryMock extends AbstractMockFactory
{
    public function create(): HtmlRendererFactory
    {
        return new HtmlRendererFactory(
            $this->createMock(Environment::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(ProjectStatisticService::class),
            $this->createMock(ActivityStatisticService::class)
        );
    }
}
