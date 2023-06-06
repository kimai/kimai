<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Model;

use App\Form\Type\YesNoType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraint;

final class Configuration
{
    private ?string $label = null;
    private string $translationDomain = 'messages';
    private string|int|null|bool|float $value = null;
    private ?string $type = null;
    /** @var array<string, mixed> */
    private array $options = [];
    private bool $enabled = true;
    private bool $required = true;
    /**
     * @var Constraint[]
     */
    private array $constraints = [];

    public function __construct(private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string|int|null|bool|float
    {
        return $this->value;
    }

    public function setValue(string|int|null|bool|float $value): Configuration
    {
        if ($this->type === CheckboxType::class || $this->type === YesNoType::class) {
            $value = (bool) $value;
        }

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

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): Configuration
    {
        $this->options = $options;

        return $this;
    }
}
