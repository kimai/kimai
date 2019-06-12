<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Widget\Type\AbstractWidgetType;
use App\Widget\Type\SimpleWidget;

/**
 * @covers \App\Widget\Type\SimpleWidget
 */
class SimpleWidgetTest extends AbstractWidgetTypeTest
{
    public function createSut(): AbstractWidgetType
    {
        return new SimpleWidget();
    }

    public function getDefaultOptions(): array
    {
        return [];
    }
}
