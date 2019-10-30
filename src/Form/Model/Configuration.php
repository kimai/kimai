<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Model;

use Symfony\Component\Validator\Constraint;

final class Configuration
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string|null
     */
    private $label;
    /**
     * @var string
     */
    private $translationDomain = 'messages';
    /**
     * @var mixed
     */
    private $value;
    /**
     * @var string
     */
    private $type;
    /**
     * @var array
     */
    private $options = [];
    /**
     * @var bool
     */
    private $enabled = true;
    /**
     * @var bool
     */
    private $required = true;
    /**
     * @var Constraint[]
     */
    private $constraints = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Configuration
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     * @return Configuration
     */
    public function setValue($value): Configuration
    {
        $this->value = $value;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): Configuration
    {
        $this->type = $type;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): Configuration
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): Configuration
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Constraint[]
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * @param Constraint[] $constraints
     * @return Configuration
     */
    public function setConstraints(array $constraints): Configuration
    {
        $this->constraints = $constraints;

        return $this;
    }

    public function getTranslationDomain(): string
    {
        return $this->translationDomain;
    }

    public function setTranslationDomain(string $translationDomain): Configuration
    {
        $this->translationDomain = $translationDomain;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): Configuration
    {
        $this->required = $required;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): Configuration
    {
        $this->options = $options;

        return $this;
    }
}
