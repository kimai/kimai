<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Plugin;

/**
 * Works with packages
 * - ZIP packages in var/packages/ (for production)
 * - and directories in var/packages/ (mainly for development)
 *
 * @internal
 */
final class PackageManager
{
    public const string PACKAGE_DIR = 'var/packages';

    public function __construct(private readonly string $projectDirectory)
    {
    }

    /**
     * @return Package[]
     */
    public function getAvailablePackages(): array
    {
        return array_merge(
            $this->findAvailablePlugins($this->projectDirectory . '/' . self::PACKAGE_DIR),
            $this->findAvailablePackages($this->projectDirectory . '/' . self::PACKAGE_DIR)
        );
    }

    /**
     * Copied from Composer\Repository\ArtifactRepository
     * @see https://github.com/composer/composer/blob/main/src/Composer/Repository/ArtifactRepository.php
     *
     * @return Package[]
     */
    private function findAvailablePackages(string $path): array
    {
        if (!file_exists($path) || !is_readable($path) || !is_dir($path)) {
            return [];
        }

        $packages = [];

        $directory = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
        $iterator = new \RecursiveIteratorIterator($directory);
        $regex = new \RegexIterator($iterator, '/^.+\.zip$/i');
        /** @var \SplFileInfo $file */
        foreach ($regex as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $path = $file->getPathname();

            $package = $this->getComposerJson($path);
            if ($package === null) {
                continue;
            }

            $content = json_decode($package, true);
            if (\JSON_ERROR_NONE !== json_last_error() || !\is_array($content)) {
                throw new \RuntimeException('Failed to parse composer.json file in: ' . $path);
            }

            $packages[] = new Package($path, PluginMetadata::createFromArray($content));
        }

        return $packages;
    }

    /**
     * Copied from Composer\Util\Zip
     * @see https://github.com/composer/composer/blob/main/src/Composer/Util/Zip.php
     */
    private function getComposerJson(string $pathToZip): ?string
    {
        if (!\extension_loaded('zip')) {
            throw new \RuntimeException('The Zip Util requires PHP\'s zip extension');
        }

        $zip = new \ZipArchive();
        if ($zip->open($pathToZip) !== true) {
            return null;
        }

        if (0 === $zip->numFiles) {
            $zip->close();

            return null;
        }

        $foundFileIndex = self::locateFile($zip, 'composer.json');

        $content = null;
        $configurationFileName = $zip->getNameIndex($foundFileIndex);
        if ($configurationFileName !== false) {
            $stream = $zip->getStream($configurationFileName);

            if (false !== $stream) {
                $content = stream_get_contents($stream);
                if ($content === false) {
                    $content = null;
                }
            }
        }

        $zip->close();

        return $content;
    }

    /**
     * Copied from Composer\Util\Zip
     * @see https://github.com/composer/composer/blob/main/src/Composer/Util/Zip.php
     */
    private static function locateFile(\ZipArchive $zip, string $filename): int
    {
        // return root composer.json if it is there and is a file
        if (false !== ($index = $zip->locateName($filename)) && $zip->getFromIndex($index) !== false) {
            return $index;
        }

        $topLevelPaths = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) {
                continue;
            }
            $dirname = \dirname($name);

            // ignore OSX specific resource fork folder
            if (strpos($name, '__MACOSX') !== false) {
                continue;
            }

            // handle archives with proper TOC
            if ($dirname === '.') {
                $topLevelPaths[$name] = true;
                if (\count($topLevelPaths) > 1) {
                    throw new \RuntimeException('Archive has more than one top level directories, and no composer.json was found on the top level, so it\'s an invalid archive. Top level paths found were: ' . implode(',', array_keys($topLevelPaths)));
                }
                continue;
            }

            // handle archives which do not have a TOC record for the directory itself
            if (false === strpos($dirname, '\\') && false === strpos($dirname, '/')) {
                $topLevelPaths[$dirname . '/'] = true;
                if (\count($topLevelPaths) > 1) {
                    throw new \RuntimeException('Archive has more than one top level directories, and no composer.json was found on the top level, so it\'s an invalid archive. Top level paths found were: ' . implode(',', array_keys($topLevelPaths)));
                }
            }
        }

        if ($topLevelPaths && false !== ($index = $zip->locateName(key($topLevelPaths) . $filename)) && $zip->getFromIndex($index) !== false) {
            return $index;
        }

        throw new \RuntimeException('No composer.json found either at the top level or within the topmost directory');
    }

    /**
     * @return Package[]
     */
    private function findAvailablePlugins(string $path): array
    {
        if (!file_exists($path) || !is_readable($path) || !is_dir($path)) {
            return [];
        }

        $packages = [];

        $paths = new \DirectoryIterator($path);
        /** @var \DirectoryIterator $file */
        foreach ($paths as $file) {
            if (!$file->isDir()) {
                continue;
            }

            $path = $file->getPathname();
            $composerFile = $path . '/composer.json';

            if (!file_exists($composerFile)) {
                continue;
            }

            $package = file_get_contents($composerFile);
            if ($package === false) {
                continue;
            }

            $content = json_decode($package, true);
            if (\JSON_ERROR_NONE !== json_last_error() || !\is_array($content)) {
                throw new \RuntimeException('Failed to parse composer.json file in: ' . $path);
            }

            $packages[] = new Package($path, PluginMetadata::createFromArray($content));
        }

        return $packages;
    }
}
