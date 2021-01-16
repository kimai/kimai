<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

/**
 * @deprecated will be removed with 2.0, use SystemConfiguration instead
 */
class FormConfiguration implements SystemBundleConfiguration
{
    private $configuration;

    public function __construct(SystemConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function find(string $key)
    {
        if (strpos($key, $this->getPrefix() . '.') === false) {
            $key = $this->getPrefix() . '.' . $key;
        }

        return $this->configuration->find($key);
    }

    public function getPrefix(): string
    {
        return 'defaults';
    }

    public function getCustomerDefaultTimezone(): ?string
    {
        return $this->find('customer.timezone');
    }

    public function getCustomerDefaultCurrency(): string
    {
        return $this->find('customer.currency');
    }

    public function getCustomerDefaultCountry(): string
    {
        return $this->find('customer.country');
    }

    public function getUserDefaultTimezone(): ?string
    {
        return $this->find('user.timezone');
    }

    public function getUserDefaultTheme(): ?string
    {
        return $this->find('user.theme');
    }

    public function getUserDefaultLanguage(): string
    {
        return $this->find('user.language');
    }

    public function getUserDefaultCurrency(): string
    {
        return $this->find('user.currency');
    }
}
