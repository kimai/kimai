<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

class SimpleWidget extends AbstractWidgetType
{
    public function getTemplateName(): string
    {
        $name = (new \ReflectionClass($this))->getShortName();

        return sprintf('widget/widget-%s.html.twig', strtolower($name));
    }
}
