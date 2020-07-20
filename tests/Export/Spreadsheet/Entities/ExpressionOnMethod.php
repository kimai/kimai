<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\Entities;

use App\Export\Annotation as Exporter;

class ExpressionOnMethod
{
    /**
     * @Exporter\Expose("accessor", label="label.accessor", exp="object.foo")
     */
    public function foo()
    {
    }
}
