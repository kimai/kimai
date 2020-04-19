<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\InvoiceDocument;
use Symfony\Component\Finder\Finder;

final class InvoiceDocumentRepository
{
    /**
     * @var array
     */
    private $documentDirs = [];

    public function __construct(array $directories)
    {
        $this->documentDirs = $directories;
    }

    public function getCustomInvoiceDirectory(): string
    {
        return $this->documentDirs[0];
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
     * Returns an array of invoice renderer, which will consist of a unique name and a controller action.
     *
     * @return InvoiceDocument[]
     */
    public function findAll()
    {
        $base = \dirname(\dirname(__DIR__)) . DIRECTORY_SEPARATOR;

        $documents = [];

        foreach ($this->documentDirs as $searchPath) {
            if (!is_dir($base . $searchPath)) {
                continue;
            }
            $finder = Finder::create()->ignoreDotFiles(true)->files()->in($base . $searchPath)->name('*.*');
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
