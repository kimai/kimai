<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\Entities;

use App\Export\Annotation as Exporter;

class InvalidPermissionOnProperty
{
    // @phpstan-ignore argument.type (the invalid type is the point of this fixture)
    #[Exporter\Expose(label: 'foo', permissions: [123])]
    public string $foo = 'foo';
}
