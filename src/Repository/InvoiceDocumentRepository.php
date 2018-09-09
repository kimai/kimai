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

class InvoiceDocumentRepository
{

    /**
     * @var array
     */
    protected $documentDirs = [];

    /**
     * @param array $directories
     */
    public function __construct(array $directories)
    {
        $this->documentDirs = $directories;
    }

    /**
     * @param string $name
     * @return InvoiceDocument|null
     */
    public function findByName(string $name)
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
        $base = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR;

        $documents = [];

        foreach($this->documentDirs as $searchPath) {
            if (!is_dir($base . $searchPath)) {
                continue;
            }
            $finder = Finder::create()->ignoreDotFiles(true)->files()->in($base . $searchPath)->name('*.*');
            foreach ($finder->getIterator() as $file) {
                $documents[] = new InvoiceDocument($file);
            }
        }

        return $documents;
    }
}
