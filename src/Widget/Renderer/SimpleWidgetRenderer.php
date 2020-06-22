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

class SimpleWidgetRenderer extends AbstractTwigRenderer
{
    public function supports(WidgetInterface $widget): bool
    {
        return $widget instanceof SimpleWidget;
    }

    /**
     * @param SimpleWidget $widget
     * @param array $options
     * @return string
     * @throws \ReflectionException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function render(WidgetInterface $widget, array $options = []): string
    {
        return $this->renderTemplate($widget->getTemplateName(), [
            'data' => $widget->getData($options),
            'options' => $widget->getOptions($options),
            'title' => $widget->getTitle(),
        ]);
    }
}
