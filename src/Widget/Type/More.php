<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

class More extends AbstractWidgetType
{
    private mixed $data = null;

    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param array $options
     * @return mixed|null
     */
    public function getData(array $options = []): mixed
    {
        return $this->data;
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-more.html.twig';
    }
}
