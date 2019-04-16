<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

class FormConfiguration implements SystemBundleConfiguration
{
    use StringAccessibleConfigTrait;

    public function getPrefix(): string
    {
        return 'defaults';
    }

    public function getCustomerDefaultTimezone(): string
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
}
