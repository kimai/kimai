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

/**
 * @ORM\Entity(repositoryClass="App\Repository\ConfigurationRepository")
 * @ORM\Table(name="kimai2_configuration",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"name"})
 *      }
 * )
 * @UniqueEntity("name")
 */
class Configuration
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     * @Assert\NotNull()
     * @Assert\Length(min=2, max=100, allowEmptyString=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255, nullable=true)
     */
    private $value;

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
     * Given $value will not be serialized before its stored, so it should be a scalar type
     * that can be casted to string.
     *
     * @param string|null|int|bool $value
     * @return Configuration
     */
    public function setValue($value): Configuration
    {
        if (null !== $value) {
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
