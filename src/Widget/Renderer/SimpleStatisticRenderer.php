<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Renderer;

use App\Widget\Type\SimpleStatistic;
use App\Widget\WidgetInterface;

class SimpleStatisticRenderer extends AbstractTwigRenderer
{
    public function supports(WidgetInterface $widget): bool
    {
        return $widget instanceof SimpleStatistic;
    }

    public function render(WidgetInterface $widget): string
    {
        $name = (new \ReflectionClass($widget))->getShortName();

        return $this->renderTemplate(sprintf('widget/widget-%s.html.twig', strtolower($name)), [
            'widget' => $widget,
        ]);
    }
}
