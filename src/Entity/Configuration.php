<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_configuration')]
#[ORM\UniqueConstraint(columns: ['name'])]
#[ORM\Entity(repositoryClass: 'App\Repository\ConfigurationRepository')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[UniqueEntity('name')]
class Configuration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;
    #[ORM\Column(name: 'name', type: 'string', length: 100, nullable: false)]
    #[Assert\NotNull]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $name = null;
    #[ORM\Column(name: 'value', type: 'text', length: 65535, nullable: true)]
    #[Assert\Length(max: 65535)]
    private ?string $value = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Configuration
     */
    public function setName(string $name): Configuration
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Given $value will not be serialized before its stored,
     * so it should be a scalar type that can be casted to string.
     */
    public function setValue(string|int|bool|null $value): Configuration
    {
        if (null === $value) {
            $this->value = null;
        } elseif ($value === false) {
            $this->value = '0';
        } else {
            $this->value = (string) $value;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): ?string
    {
        return $this->value;
    }
}
