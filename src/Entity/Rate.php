<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass(repositoryClass="App\Repository\RateRepository")
 */
class Rate
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     */
    private $user;
    /**
     * @var float
     *
     * @ORM\Column(name="rate", type="float", nullable=false)
     * @Assert\GreaterThanOrEqual(0)
     */
    private $rate = 0.00;
    /**
     * @var bool
     *
     * @ORM\Column(name="fixed", type="boolean", nullable=false)
     * @Assert\NotNull()
     */
    private $isFixed = false;

    /**
     * Get entry id, returns null for new entities which were not persisted.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setUser(?User $user): Rate
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setRate(float $rate): Rate
    {
        $this->rate = $rate;

        return $this;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function isFixed(): bool
    {
        return $this->isFixed;
    }

    public function setIsFixed(bool $isFixed): Rate
    {
        $this->isFixed = $isFixed;

        return $this;
    }

    public function getScore(): int
    {
        return 0;
    }
}
