<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

final class FileHelper
{
    /**
     * @var string
     */
    private $dataDir;

    public function __construct(string $dataDir)
    {
        $this->dataDir = $dataDir;
    }

    public function getDataSubdirectory(string $directory): string
    {
        $subDirectory = $this->dataDir . '/' . rtrim(ltrim($directory, '/'), '/') . '/';

        $this->makeDir($subDirectory);

        if (!is_dir($subDirectory)) {
            throw new \Exception(sprintf('Directory "%s" does not exist', $subDirectory));
        }

        if (!is_writable($subDirectory)) {
            throw new \Exception(sprintf('Directory "%s" is not writable', $subDirectory));
        }

        return $subDirectory;
    }

    public function makeDir(string $directory)
    {
        if (is_dir($directory)) {
            return;
        }

        if (false === @mkdir($directory)) {
            throw new \Exception(sprintf('Failed to create directory "%s", check file permissions', $directory));
        }
    }

    public function saveFile(string $filename, $data)
    {
        $result = @file_put_contents($filename, $data);
        if ($result === false) {
            throw new \Exception('File "%s" could not be written');
        }
    }
}
