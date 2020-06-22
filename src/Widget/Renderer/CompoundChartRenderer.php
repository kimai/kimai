<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Renderer;

use App\Widget\Type\CompoundChart;
use App\Widget\WidgetInterface;

class CompoundChartRenderer extends AbstractTwigRenderer
{
    public function supports(WidgetInterface $widget): bool
    {
        return ($widget instanceof CompoundChart);
    }

    public function render(WidgetInterface $widget, array $options = []): string
    {
        return $this->renderTemplate('widget/section-chart.html.twig', [
            'title' => $widget->getTitle(),
            'widgets' => $widget->getData(),
        ]);
    }
}
