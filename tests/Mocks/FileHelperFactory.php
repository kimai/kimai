<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use App\Utils\FileHelper;

class FileHelperFactory extends AbstractMockFactory
{
    public function create(): FileHelper
    {
        $data = realpath(__DIR__ . '/../../var/data/');

        return new FileHelper($data);
    }
}
