<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Importer;

interface ImportReaderInterface
{
    /**
     * @param string $input
     * @return \Iterator
     * @throws ImportNotFoundException
     */
    public function read(string $input): \Iterator;
}
