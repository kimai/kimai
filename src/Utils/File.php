<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class File
{
    /**
     * @param string $filename
     * @return int
     * @throws FileNotFoundException
     */
    public function getPermissions(string $filename): int
    {
        if (!file_exists($filename)) {
            throw new FileNotFoundException(sprintf('Unknown file "%s"', $filename));
        }

        return fileperms($filename);
    }
}
