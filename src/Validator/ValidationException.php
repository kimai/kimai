<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator;

class ValidationException extends \RuntimeException
{
    public function __construct(string $message = null)
    {
        if ($message === null) {
            $message = 'Validation failed';
        }
        parent::__construct($message, 400);
    }
}
