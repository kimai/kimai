<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Renderer;

use App\Widget\Type\SimpleWidget;
use App\Widget\WidgetInterface;
use ReflectionClass;

class SimpleWidgetRenderer extends AbstractTwigRenderer
{
    public function supports(WidgetInterface $widget): bool
    {
        return $widget instanceof SimpleWidget;
    }

    public function render(WidgetInterface $widget): string
    {
        $name = (new ReflectionClass($widget))->getShortName();

        return $this->renderTemplate(sprintf('widget/widget-%s.html.twig', strtolower($name)), [
            'data' => $widget->getData(),
            'options' => $widget->getOptions(),
            'title' => $widget->getTitle(),
        ]);
    }
}
