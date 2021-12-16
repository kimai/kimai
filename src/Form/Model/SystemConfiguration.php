<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Model;

final class SystemConfiguration
{
    /** @deprecated since 1.16.10 */
    public const SECTION_ROUNDING = 'rounding';
    /** @deprecated since 1.16.10 */
    public const SECTION_LOCKDOWN = 'lockdown_period';
    /** @deprecated since 1.16.10 */
    public const SECTION_TIMESHEET = 'timesheet';
    /** @deprecated since 1.16.10 */
    public const SECTION_FORM_INVOICE = 'invoice';
    /** @deprecated since 1.16.10 */
    public const SECTION_FORM_CUSTOMER = 'customer';
    /** @deprecated since 1.16.10 */
    public const SECTION_FORM_USER = 'user';
    /** @deprecated since 1.16.10 */
    public const SECTION_THEME = 'theme';
    /** @deprecated since 1.16.10 */
    public const SECTION_AUTHENTICATION = 'authentication';
    /** @deprecated since 1.16.10 */
    public const SECTION_CALENDAR = 'calendar';
    /** @deprecated since 1.16.10 */
    public const SECTION_BRANDING = 'branding';

    /**
     * @var string|null
     */
    private $section;
    /**
     * @var string|null
     */
    private $translation;
    /**
     * @var string|null
     */
    private $translationDomain = 'system-configuration';
    /**
     * @var Configuration[]
     */
    private $configuration = [];

    public function __construct(?string $section = null)
    {
        $this->section = $section;
    }

    public function getSection(): ?string
    {
        return $this->section;
    }

    /**
     * @deprecated since 1.16.10
     * @param string|null $section
     * @return $this
     */
    public function setSection(?string $section): SystemConfiguration
    {
        $this->section = $section;

        return $this;
    }

    public function setTranslation(string $translation): SystemConfiguration
    {
        $this->translation = $translation;

        return $this;
    }

    public function getTranslation(): string
    {
        return $this->translation ?? $this->section;
    }

    public function setTranslationDomain(string $domain): SystemConfiguration
    {
        $this->translationDomain = $domain;

        return $this;
    }

    public function getTranslationDomain(): string
    {
        return $this->translationDomain;
    }

    /**
     * @return Configuration[]
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getConfigurationByName(string $name): ?Configuration
    {
        foreach ($this->configuration as $configuration) {
            if ($configuration->getName() === $name) {
                return $configuration;
            }
        }

        return null;
    }

    /**
     * @param Configuration[] $configuration
     * @return SystemConfiguration
     */
    public function setConfiguration(array $configuration): SystemConfiguration
    {
        $this->configuration = $configuration;

        return $this;
    }

    public function addConfiguration(Configuration $configuration): SystemConfiguration
    {
        $this->configuration[] = $configuration;

        return $this;
    }
}
