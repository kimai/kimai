<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

class InvoiceDocument
{
    /**
     * @var \SplFileInfo
     */
    private $file;

    /**
     * @param \SplFileInfo $file
     */
    public function __construct(\SplFileInfo $file)
    {
        $this->file = $file;
    }

    /**
     * @return string
     */
    public function getId()
    {
        $file = $this->file->getFilename();

        return substr($file, 0, strpos($file, '.'));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return basename($this->getFilename());
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->file->getRealPath();
    }
}
