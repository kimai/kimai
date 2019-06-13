<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Renderer;

use App\Widget\Type\CompoundRow;
use App\Widget\WidgetInterface;

class CompoundRowRenderer extends AbstractTwigRenderer
{
    public function supports(WidgetInterface $widget): bool
    {
        return ($widget instanceof CompoundRow);
    }

    public function render(WidgetInterface $widget, array $options = []): string
    {
        return $this->renderTemplate('widget/section-simple.html.twig', [
            'title' => $widget->getTitle(),
            'widgets' => $widget->getData(),
        ]);
    }
}
