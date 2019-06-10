<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget;

class WidgetService
{
    /**
     * @var WidgetRendererInterface[]
     */
    protected $renderer = [];

    /**
     * @param WidgetRendererInterface[] $renderer
     */
    public function __construct(iterable $renderer)
    {
        foreach ($renderer as $render) {
            $this->addRenderer($render);
        }
    }

    public function addRenderer(WidgetRendererInterface $renderer): WidgetService
    {
        $this->renderer[] = $renderer;

        return $this;
    }

    /**
     * @param WidgetInterface $widget
     * @return WidgetRendererInterface
     * @throws WidgetException
     */
    public function findRenderer(WidgetInterface $widget): WidgetRendererInterface
    {
        foreach ($this->renderer as $renderer) {
            if ($renderer->supports($widget)) {
                return $renderer;
            }
        }

        throw new WidgetException(sprintf('No renderer available for widget "%s"', get_class($widget)));
    }

    /**
     * @return WidgetRendererInterface[]
     */
    public function getRenderer(): array
    {
        return $this->renderer;
    }
}
