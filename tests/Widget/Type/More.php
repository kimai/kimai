<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Widget\Type\AbstractWidgetType;

class More extends AbstractWidgetType
{
    private mixed $data = null;

    public function setData(mixed $data): void
    {
        $this->data = $data;
    }

    /**
     * @param array<string, string|bool|int|null> $options
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
