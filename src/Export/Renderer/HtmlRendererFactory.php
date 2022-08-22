<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Renderer;

use App\Activity\ActivityStatisticService;
use App\Project\ProjectStatisticService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

final class HtmlRendererFactory
{
    public function __construct(
        private Environment $twig,
        private EventDispatcherInterface $dispatcher,
        private ProjectStatisticService $projectStatisticService,
        private ActivityStatisticService $activityStatisticService
    ) {
    }

    public function create(string $id, string $template): HtmlRenderer
    {
        $renderer = new HtmlRenderer($this->twig, $this->dispatcher, $this->projectStatisticService, $this->activityStatisticService);
        $renderer->setId($id);
        $renderer->setTemplate($template);

        return $renderer;
    }
}
