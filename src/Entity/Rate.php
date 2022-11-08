<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

trait Rate
{
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @OA\Property(ref="#/components/schemas/User")
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    #[ORM\JoinColumn(onDelete: 'CASCADE', nullable: true)]
    private ?User $user = null;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Assert\GreaterThanOrEqual(0)
     */
    #[ORM\Column(name: 'rate', type: 'float', nullable: false)]
    private float $rate = 0.00;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     */
    #[ORM\Column(name: 'internal_rate', type: 'float', nullable: true)]
    private ?float $internalRate = null;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Assert\NotNull()
     */
    #[ORM\Column(name: 'fixed', type: 'boolean', nullable: false)]
    private bool $isFixed = false;

    /**
     * Get entry id, returns null for new entities which were not persisted.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setRate(float $rate): self
    {
        $this->rate = $rate;

        return $this;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function setInternalRate(?float $rate): self
    {
        $this->internalRate = $rate;

        return $this;
    }

    public function getInternalRate(): ?float
    {
        return $this->internalRate;
    }

    public function isFixed(): bool
    {
        return $this->isFixed;
    }

    public function setIsFixed(bool $isFixed): self
    {
        $this->isFixed = $isFixed;

        return $this;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }
}
