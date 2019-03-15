<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Plugin;

interface PluginInterface
{
    /**
     * Returns an array of strings, which mark the allowed license states.
     *
     * @return array
     */
    public function getLicenseRequirements(): array;

    /**
     * Returns the MD5 checksum of your composer.json.
     *
     * @return string
     */
    public function getChecksum(): string;

    /**
     * @return string
     */
    public function getName();
}
