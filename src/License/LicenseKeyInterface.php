<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\License;

interface LicenseKeyInterface
{
    /**
     * @return string
     * @throws LicenseException
     */
    public function getPublicKey(): string;

    /**
     * @param string $message
     * @return string
     * @throws LicenseException
     */
    public function decrypt($message): string;
}
