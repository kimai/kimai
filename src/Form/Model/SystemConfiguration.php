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
    private ?string $translation = null;
    private string $translationDomain = 'system-configuration';
    /**
     * @var Configuration[]
     */
    private array $configuration = [];

    public function __construct(private ?string $section = null)
    {
    }

    public function getSection(): ?string
    {
        return $this->section;
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
