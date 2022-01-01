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
    private $twig;
    private $dispatcher;
    private $projectStatisticService;
    private $activityStatisticService;

    public function __construct(Environment $twig, EventDispatcherInterface $dispatcher, ProjectStatisticService $projectStatisticService, ActivityStatisticService $activityStatisticService)
    {
        $this->twig = $twig;
        $this->dispatcher = $dispatcher;
        $this->projectStatisticService = $projectStatisticService;
        $this->activityStatisticService = $activityStatisticService;
    }

    public function create(string $id, string $template): HtmlRenderer
    {
        $renderer = new HtmlRenderer($this->twig, $this->dispatcher, $this->projectStatisticService, $this->activityStatisticService);
        $renderer->setId($id);
        $renderer->setTemplate($template);

        return $renderer;
    }
}
