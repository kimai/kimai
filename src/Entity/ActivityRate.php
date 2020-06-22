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
 * @ORM\Table(name="kimai2_activities_rates",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"user_id", "activity_id"}),
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\ActivityRateRepository")
 * @UniqueEntity({"user", "activity"}, ignoreNull=false)
 */
class ActivityRate implements RateInterface
{
    use Rate;

    /**
     * @var Activity
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Activity")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $activity;

    public function setActivity(?Activity $activity): ActivityRate
    {
        $this->activity = $activity;

        return $this;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function getScore(): int
    {
        return 5;
    }
}
