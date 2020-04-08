<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Model;

class SystemConfiguration
{
    public const SECTION_ROUNDING = 'rounding';
    public const SECTION_TIMESHEET = 'timesheet';
    public const SECTION_FORM_INVOICE = 'invoice';
    public const SECTION_FORM_CUSTOMER = 'form_customer';
    public const SECTION_FORM_USER = 'form_user';
    public const SECTION_THEME = 'theme';
    public const SECTION_CALENDAR = 'calendar';
    public const SECTION_BRANDING = 'branding';

    /**
     * @var string
     */
    private $section;
    /**
     * @var Configuration[]
     */
    private $configuration;

    /**
     * @return string
     */
    public function getSection(): string
    {
        return $this->section;
    }

    /**
     * @param string $section
     * @return SystemConfiguration
     */
    public function setSection(string $section)
    {
        $this->section = $section;

        return $this;
    }

    /**
     * @return Configuration[]
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @param Configuration[] $configuration
     * @return SystemConfiguration
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;

        return $this;
    }
}
