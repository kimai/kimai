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
     * @param RendererInterface $renderer
     * @return $this
     */
    public function addRenderer(RendererInterface $renderer)
    {
        $this->renderer[] = $renderer;

        return $this;
    }

    /**
     * Returns an array of export renderer.
     *
     * @return RendererInterface[]
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    /**
     * @param string $id
     * @return RendererInterface|null
     */
    public function getRendererById(string $id)
    {
        foreach ($this->renderer as $renderer) {
            if ($renderer->getId() === $id) {
                return $renderer;
            }
        }

        return null;
    }
}
