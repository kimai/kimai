<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Widget\WidgetInterface;
use App\Widget\WidgetService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class WidgetExtension extends AbstractExtension
{
    /**
     * @var WidgetService
     */
    protected $service;

    public function __construct(WidgetService $service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('render_widget', [$this, 'renderWidget'], ['is_safe' => ['html']]),
        ];
    }

    public function renderWidget(WidgetInterface $widget)
    {
        $renderer = $this->service->findRenderer($widget);

        return $renderer->render($widget);
    }
}
