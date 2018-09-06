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
 * Project
 *
 * @ORM\Table(name="projects")
 * @ORM\Entity(repositoryClass="App\Repository\ProjectRepository")
 */
class Project
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
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Customer", inversedBy="projects")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Assert\NotNull()
     */
    private $customer;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @Assert\NotNull()
     * @Assert\Length(min=2, max=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="order_number", type="text", length=20, nullable=true)
     * @Assert\Length(max=20)
     */
    private $orderNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", length=65535, nullable=true)
     */
    private $comment;

    /**
     * @var bool
     *
     * @ORM\Column(name="visible", type="boolean", nullable=false)
     * @Assert\NotNull()
     */
    private $visible = true;

    /**
     * @var float
     *
     * @ORM\Column(name="budget", type="decimal", precision=10, scale=2, nullable=false)
     * @Assert\NotNull()
     */
    private $budget = 0.00;

    /**
     * @var Activity[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Activity", mappedBy="project")
     */
    private $activities;

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
     * Get projectid
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param $customer
     * @return $this
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Project
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return Project
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set visible
     *
     * @param bool $visible
     * @return Project
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible
     *
     * @return bool
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set budget
     *
     * @param float $budget
     * @return Project
     */
    public function setBudget($budget)
    {
        $this->budget = $budget;

        return $this;
    }

    /**
     * Get budget
     *
     * @return float
     */
    public function getBudget()
    {
        return $this->budget;
    }

    /**
     * @param Activity[] $activities
     * @return Project
     */
    public function setActivities($activities)
    {
        $this->activities = $activities;

        return $this;
    }

    /**
     * @return Activity[]
     */
    public function getActivities()
    {
        return $this->activities;
    }

    /**
     * @return string
     */
    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    /**
     * @param string $orderNumber
     * @return Project
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;

        return $this;
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
     * @return Project
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
     * @return Project
     */
    public function setHourlyRate(?float $hourlyRate)
    {
        $this->hourlyRate = $hourlyRate;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
