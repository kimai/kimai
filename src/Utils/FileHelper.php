<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\UnicodeString;

final class FileHelper
{
    private Filesystem $filesystem;

    public function __construct(private string $dataDir)
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * @CloudRequired
     */
    public function setDataDirectory(string $directory): void
    {
        $this->dataDir = $directory;
    }

    public function getDataDirectory(string $subDirectory = null): string
    {
        $directory = $this->dataDir . '/';

        if (!empty($subDirectory)) {
            $directory .= rtrim(ltrim($subDirectory, '/'), '/') . '/';
        }

        $this->makeDir($directory);

        if (!is_dir($directory)) {
            throw new \Exception(\sprintf('Directory "%s" does not exist', $directory));
        }

        if (!is_writable($directory)) {
            throw new \Exception(\sprintf('Directory "%s" is not writable', $directory));
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

    public static function convertToAsciiFilename(string $filename): string
    {
        $filename = new UnicodeString($filename);
        $filename = (string) $filename->collapseWhitespace()->trim()->replace(PHP_EOL, '')->replace(' ', '_');

        $dangerousCharacters = ['"', "'", '&', '/', '\\', '?', '#', '%'];
        $filename = str_replace($dangerousCharacters, ' ', $filename);

        $filename = new UnicodeString($filename);
        $filename = (string) $filename->collapseWhitespace()->replace(' ', '_')->ascii()->trim();
        $filename = preg_replace('/[^a-zA-Z0-9\x7f-\xff\-]++/', ' ', $filename);
        $filename = str_replace(' ', '_', trim($filename));

        return $filename;
    }
}
