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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_activities_rates')]
#[ORM\UniqueConstraint(columns: ['user_id', 'activity_id'])]
#[ORM\Entity(repositoryClass: 'App\Repository\ActivityRateRepository')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[UniqueEntity(['user', 'activity'], ignoreNull: false)]
#[Serializer\ExclusionPolicy('all')]
class ActivityRate implements RateInterface
{
    use Rate;

    #[ORM\ManyToOne(targetEntity: Activity::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Activity $activity = null;

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
