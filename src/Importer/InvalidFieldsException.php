<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Importer;

use Throwable;

class InvalidFieldsException extends \Exception
{
    private $fields = [];

    public function __construct(array $fields, $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('Missing fields: %s', implode(', ', $fields)), $code, $previous);
        $this->fields = $fields;
    }

    public function getFields(): array
    {
        return $this->fields;
    }
}
