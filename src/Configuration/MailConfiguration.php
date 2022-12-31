<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

final class MailConfiguration
{
    public function __construct(private string $mailFrom)
    {
    }

    public function getFromAddress(): ?string
    {
        if (empty($this->mailFrom)) {
            return null;
        }

        return $this->mailFrom;
    }
}
