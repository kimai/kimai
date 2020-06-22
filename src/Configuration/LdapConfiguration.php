<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

class LdapConfiguration
{
    /**
     * @var array
     */
    protected $settings = [];

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function getRoleParameters(): array
    {
        return (array) $this->settings['role'];
    }

    public function getUserParameters(): array
    {
        return (array) $this->settings['user'];
    }

    public function getConnectionParameters(): array
    {
        return (array) $this->settings['connection'];
    }
}
