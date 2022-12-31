<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Model\InvoiceDocument;
use Symfony\Component\Finder\Finder;

final class InvoiceDocumentRepository
{
    public const DEFAULT_DIRECTORY = 'templates/invoice/renderer/';

    /**
     * @var array<string>
     */
    private array $documentDirs = [];

    public function __construct(array $directories)
    {
        foreach ($directories as $directory) {
            $this->addDirectory($directory);
        }
    }

    /**
     * @CloudRequired
     */
    public function addDirectory(string $directory)
    {
        $this->documentDirs[] = $directory;

        return $this;
    }

    /**
     * @CloudRequired
     */
    public function removeDirectory(string $directory)
    {
        if (($key = array_search($directory, $this->documentDirs)) !== false) {
            unset($this->documentDirs[$key]);
        }

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function remove(InvoiceDocument $invoiceDocument): void
    {
        if (stripos($invoiceDocument->getFilename(), $this->getUploadDirectory()) === false) {
            throw new \InvalidArgumentException('Cannot delete built-in invoice template');
        }

        @unlink(realpath($invoiceDocument->getFilename()));
    }

    public function getUploadDirectory(): string
    {
        // reverse the array, as bundles can register invoice directories a well (as prepend extensions)
        // and then the first entries are the directories from the bundles and not the default ones registered in Kimai
        foreach (array_reverse($this->documentDirs) as $dir) {
            if ($dir === self::DEFAULT_DIRECTORY) {
                continue;
            }

            return $dir;
        }

        throw new \Exception('Unknown upload directory');
    }

    public function findByName(string $name): ?InvoiceDocument
    {
        foreach ($this->findAll() as $document) {
            if ($document->getId() === $name) {
                return $document;
            }
        }

        return null;
    }

    /**
     * Returns an array of all custom invoice documents.
     *
     * @return InvoiceDocument[]
     */
    public function findCustom(): array
    {
        $paths = [];
        foreach ($this->documentDirs as $dir) {
            if ($dir === self::DEFAULT_DIRECTORY) {
                continue;
            }
            $paths[] = $dir;
        }

        return $this->findByPaths($paths);
    }

    /**
     * Returns an array of all original Kimai documents.
     *
     * @return InvoiceDocument[]
     */
    public function findBuiltIn(): array
    {
        foreach ($this->documentDirs as $dir) {
            if ($dir === self::DEFAULT_DIRECTORY) {
                return $this->findByPaths([$dir]);
            }
        }

        return [];
    }

    /**
     * Returns an array of invoice documents.
     *
     * @return InvoiceDocument[]
     */
    public function findAll(): array
    {
        return $this->findByPaths($this->documentDirs);
    }

    /**
     * Returns an array of invoice documents.
     *
     * @return InvoiceDocument[]
     */
    private function findByPaths(array $paths): array
    {
        $base = \dirname(\dirname(__DIR__)) . DIRECTORY_SEPARATOR;

        $documents = [];

        foreach ($paths as $searchPath) {
            $searchDir = $searchPath;
            if ($searchDir[0] !== '/') {
                $searchDir = $base . $searchPath;
            }

            if (!is_dir($searchDir)) {
                continue;
            }

            $finder = Finder::create()->ignoreDotFiles(true)->files()->in($searchDir)->name('*.*');
            foreach ($finder->getIterator() as $file) {
                $doc = new InvoiceDocument($file);
                // the first found invoice document wins
                if (!isset($documents[$doc->getId()])) {
                    $documents[$doc->getId()] = $doc;
                }
            }
        }

        return $documents;
    }
}
