<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

final class InvoiceDocument
{
    /**
     * @var \SplFileInfo
     */
    private $file;

    public function __construct(\SplFileInfo $file)
    {
        $this->file = $file;
    }

    public function getId(): string
    {
        $file = $this->file->getFilename();

        return substr($file, 0, strpos($file, '.'));
    }

    public function getName(): string
    {
        return basename($this->getFilename());
    }

    public function getFilename(): string
    {
        return $this->file->getRealPath();
    }

    public function getFileExtension(): string
    {
        return $this->file->getExtension();
    }

    public function getLastChange(): int
    {
        return $this->file->getMTime();
    }
}
