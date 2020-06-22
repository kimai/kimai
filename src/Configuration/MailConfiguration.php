<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

class MailConfiguration
{
    public function getFromAddress(): ?string
    {
        $from = getenv('MAILER_FROM');

        if ($from === false || empty($from)) {
            return null;
        }

        return $from;
    }
}
