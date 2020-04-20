<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget;

use App\Repository\WidgetRepository;

class WidgetService
{
    /**
     * @var WidgetRendererInterface[]
     */
    protected $renderer = [];
    /**
     * @var WidgetRepository
     */
    protected $repository;

    /**
     * @param WidgetRepository $repository
     * @param WidgetRendererInterface[] $renderer
     */
    public function __construct(WidgetRepository $repository, iterable $renderer)
    {
        foreach ($renderer as $render) {
            $this->addRenderer($render);
        }
        $this->repository = $repository;
    }

    /**
     * @param string $widget
     * @return bool
     */
    public function hasWidget(string $widget): bool
    {
        return $this->repository->has($widget);
    }

    public function getWidget(string $widget): WidgetInterface
    {
        return $this->repository->get($widget);
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

        throw new WidgetException(sprintf('No renderer available for widget "%s"', \get_class($widget)));
    }

    /**
     * @return WidgetRendererInterface[]
     */
    public function getRenderer(): array
    {
        return $this->renderer;
    }
}
