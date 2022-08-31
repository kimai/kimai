<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\Entities;

use App\Export\Annotation as Exporter;

class MethodRequiresParams
{
    /**
     * @Exporter\Expose("accessor", label="accessor")
     */
    public function foo(string $foo)
    {
    }
}
