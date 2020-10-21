<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Importer;

use League\Csv\Reader;

final class CsvReader implements ImportReaderInterface
{
    private $delimiter;

    public function __construct(string $delimiter)
    {
        $this->delimiter = $delimiter;
    }

    public function read(string $input): \Iterator
    {
        if (!is_readable($input)) {
            throw new ImportNotFoundException();
        }

        $csv = Reader::createFromPath($input, 'r');
        $csv->setDelimiter($this->delimiter);
        $csv->setHeaderOffset(0);

        return $csv->getRecords();
    }
}
