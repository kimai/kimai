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
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Timesheet entity.
 *
 * @ORM\Table(
 *     name="timesheet",
 *     indexes={
 *          @ORM\Index(columns={"user"}),
 *          @ORM\Index(columns={"activity_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\TimesheetRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Assert\Callback("validate")
 */
class Timesheet
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
     * @var \DateTime
     *
     * @ORM\Column(name="start_time", type="datetime", nullable=false)
     * @Assert\NotNull()
     */
    private $begin;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_time", type="datetime", nullable=true)
     */
    private $end;

    /**
     * @var int
     *
     * @ORM\Column(name="duration", type="integer", nullable=true)
     * @Assert\GreaterThanOrEqual(0)
     */
    private $duration = 0;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $user;

    /**
     * @var Activity
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Activity", inversedBy="timesheets")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $activity;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Project", inversedBy="timesheets")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $project;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private $description;

    /**
     * @var float
     *
     * @ORM\Column(name="rate", type="decimal", precision=10, scale=2, nullable=false)
     * @Assert\GreaterThanOrEqual(0)
     */
    private $rate = 0.00;

    /**
     * @var float
     *
     * @ORM\Column(name="fixed_rate", type="decimal", precision=10, scale=2, nullable=true)
     * @Assert\GreaterThanOrEqual(0)
     */
    private $fixedRate = null;

    /**
     * @var float
     *
     * @ORM\Column(name="hourly_rate", type="decimal", precision=10, scale=2, nullable=true)
     * @Assert\GreaterThanOrEqual(0)
     */
    private $hourlyRate = null;

    /**
     * Get entry id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getBegin()
    {
        return $this->begin;
    }

    /**
     * @param \DateTime $begin
     * @return Timesheet
     */
    public function setBegin($begin)
    {
        $this->begin = $begin;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @param \DateTime $end
     * @return Timesheet
     */
    public function setEnd($end)
    {
        $this->end = $end;

        if (null === $end) {
            $this->duration = 0;
            $this->rate = 0;
        }

        return $this;
    }

    /**
     * Set duration
     *
     * @param int $duration
     * @return Timesheet
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     * Do not rely on the results of this method for active records.
     *
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return Timesheet
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set activity
     *
     * @param Activity $activity
     * @return Timesheet
     */
    public function setActivity($activity)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get Activity
     *
     * @return Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param Project $project
     * @return Timesheet
     */
    public function setProject(Project $project)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Timesheet
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set rate
     *
     * @param float $rate
     * @return Timesheet
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * Get rate
     *
     * @return float
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @return float
     */
    public function getFixedRate(): ?float
    {
        return $this->fixedRate;
    }

    /**
     * @param float $fixedRate
     * @return Timesheet
     */
    public function setFixedRate(?float $fixedRate)
    {
        $this->fixedRate = $fixedRate;

        return $this;
    }

    /**
     * @return float
     */
    public function getHourlyRate(): ?float
    {
        return $this->hourlyRate;
    }

    /**
     * @param float $hourlyRate
     * @return Timesheet
     */
    public function setHourlyRate(?float $hourlyRate)
    {
        $this->hourlyRate = $hourlyRate;

        return $this;
    }

    /**
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if (null === ($activity = $this->getActivity())) {
            $context->buildViolation('A timesheet must have an activity.')
                ->atPath('activity')
                ->setTranslationDomain('validators')
                ->addViolation();
        }

        if (null === ($project = $this->getProject())) {
            $context->buildViolation('A timesheet must have a project.')
                ->atPath('project')
                ->setTranslationDomain('validators')
                ->addViolation();
        }

        if (null !== $activity && null !== $project) {
            if (null !== $activity->getProject() && $activity->getProject() !== $project) {
                $context->buildViolation('Project mismatch, project specific activity and timesheet project are different.')
                    ->atPath('project')
                    ->setTranslationDomain('validators')
                    ->addViolation();
            }

            if (null === $this->getEnd() && $activity->getVisible() === false) {
                $context->buildViolation('Cannot start a disabled activity.')
                    ->atPath('activity')
                    ->setTranslationDomain('validators')
                    ->addViolation();
            }

            if (null === $this->getEnd() && $project->getVisible() === false) {
                $context->buildViolation('Cannot start a disabled project.')
                    ->atPath('project')
                    ->setTranslationDomain('validators')
                    ->addViolation();
            }

            if (null === $this->getEnd() && $project->getCustomer()->getVisible() === false) {
                $context->buildViolation('Cannot start a disabled customer.')
                    ->atPath('customer')
                    ->setTranslationDomain('validators')
                    ->addViolation();
            }
        }

        if (null === $this->getBegin()) {
            $context->buildViolation('You must submit a begin date.')
                ->atPath('begin')
                ->setTranslationDomain('validators')
                ->addViolation();
        } else {
            if (null !== $this->getBegin() && null !== $this->getEnd() && $this->getEnd()->getTimestamp() < $this->getBegin()->getTimestamp()) {
                $context->buildViolation('End date must not be earlier then start date.')
                    ->atPath('end')
                    ->setTranslationDomain('validators')
                    ->addViolation();
            }

            if (time() < $this->getBegin()->getTimestamp()) {
                $context->buildViolation('The begin date cannot be in the future.')
                    ->atPath('begin')
                    ->setTranslationDomain('validators')
                    ->addViolation();
            }
        }
    }
}
