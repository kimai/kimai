<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

/**
 * @deprecated since 2.31.0, use AbstractWidget instead
 */
abstract class AbstractWidgetType extends AbstractWidget
{
    public function setOptions(array $options): void
    {
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }
    }
}
