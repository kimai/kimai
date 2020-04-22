<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use Symfony\Component\Filesystem\Filesystem;

final class FileHelper
{
    /**
     * @var string
     */
    private $dataDir;
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(string $dataDir)
    {
        $this->dataDir = $dataDir;
        $this->filesystem = new Filesystem();
    }

    public function getDataDirectory(string $subDirectory = null): string
    {
        $directory = $this->dataDir . '/';

        if (!empty($subDirectory)) {
            $directory .= rtrim(ltrim($subDirectory, '/'), '/') . '/';
        }

        $this->makeDir($directory);

        if (!is_dir($directory)) {
            throw new \Exception(sprintf('Directory "%s" does not exist', $directory));
        }

        if (!is_writable($directory)) {
            throw new \Exception(sprintf('Directory "%s" is not writable', $directory));
        }

        return $directory;
    }

    public function makeDir(string $directory)
    {
        $this->filesystem->mkdir($directory);
    }

    public function saveFile(string $filename, $data)
    {
        $this->filesystem->dumpFile($filename, $data);
    }

    public function removeFile(string $filename)
    {
        $this->filesystem->remove($filename);
    }
}
