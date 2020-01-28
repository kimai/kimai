<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Importer;

use Throwable;

class UnknownUserException extends \Exception
{
    /**
     * @var string
     */
    private $username;

    public function __construct(string $username, $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('Unknown user: %s', $username), $code, $previous);
        $this->username = $username;
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
