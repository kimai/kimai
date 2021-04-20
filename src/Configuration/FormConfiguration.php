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
        return $this->configuration->getCustomerDefaultTimezone();
    }

    public function getCustomerDefaultCurrency(): string
    {
        return $this->configuration->getCustomerDefaultCurrency();
    }

    public function getCustomerDefaultCountry(): string
    {
        return $this->configuration->getCustomerDefaultCountry();
    }

    public function getUserDefaultTimezone(): ?string
    {
        return $this->configuration->getUserDefaultTimezone();
    }

    public function getUserDefaultTheme(): ?string
    {
        return $this->configuration->getUserDefaultTheme();
    }

    public function getUserDefaultLanguage(): string
    {
        return $this->configuration->getUserDefaultLanguage();
    }

    public function getUserDefaultCurrency(): string
    {
        return $this->configuration->getUserDefaultCurrency();
    }
}
