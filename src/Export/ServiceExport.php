<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

class ServiceExport
{
    /**
     * @var RendererInterface[]
     */
    protected $renderer = [];

    /**
     * @param RendererInterface[] $renderer
     */
    public function __construct(iterable $renderer)
    {
        foreach ($renderer as $render) {
            $this->addRenderer($render);
        }
    }

    public function addRenderer(RendererInterface $renderer): ServiceExport
    {
        $this->renderer[] = $renderer;

        return $this;
    }

    /**
     * @return RendererInterface[]
     */
    public function getRenderer(): array
    {
        return $this->renderer;
    }

    public function getRendererById(string $id): ?RendererInterface
    {
        foreach ($this->renderer as $renderer) {
            if ($renderer->getId() === $id) {
                return $renderer;
            }
        }

        return null;
    }
}
