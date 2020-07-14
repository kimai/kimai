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
use Symfony\Component\Validator\Constraints as Assert;

trait BudgetTrait
{
    /**
     * The total monetary budget, will be zero if unconfigured.
     *
     * @var float
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Entity"})
     *
     * @ORM\Column(name="budget", type="float", nullable=false)
     * @Assert\NotNull()
     */
    private $budget = 0.00;

    /**
     * The time budget in seconds, will be be zero if unconfigured.
     *
     * @var int
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Entity"})
     *
     * @ORM\Column(name="time_budget", type="integer", nullable=false)
     * @Assert\NotNull()
     */
    private $timeBudget = 0;

    /**
     * @param float $budget
     * @return self
     */
    public function setBudget(?float $budget)
    {
        $this->budget = $budget;

        return $this;
    }

    /**
     * @return float
     */
    public function getBudget()
    {
        return $this->budget;
    }

    /**
     * @param int $seconds
     * @return self
     */
    public function setTimeBudget(?int $seconds)
    {
        $this->timeBudget = $seconds;

        return $this;
    }

    public function getTimeBudget(): int
    {
        return $this->timeBudget;
    }
}
