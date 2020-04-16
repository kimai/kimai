<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\User;

/**
 * @internal
 */
final class InvoiceItem
{
    /**
     * @var float
     */
    private $fixedRate;
    /**
     * @var float
     */
    private $hourlyRate;
    /**
     * @var float
     */
    private $rate = 0.00;
    /**
     * @var float
     */
    private $rateInternal = 0.00;
    /**
     * @var float
     */
    private $amount = 0;
    /**
     * @var string
     */
    private $description;
    /**
     * @var int
     */
    private $duration = 0;
    /**
     * @var \DateTime
     */
    private $begin;
    /**
     * @var \DateTime
     */
    private $end;
    /**
     * @var User
     */
    private $user;
    /**
     * @var Activity
     */
    private $activity;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var array
     */
    private $additionalFields = [];
    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $category;

    public function addAdditionalField(string $name, ?string $value): InvoiceItem
    {
        $this->additionalFields[$name] = $value;

        return $this;
    }

    public function getAdditionalFields(): array
    {
        return $this->additionalFields;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(?Activity $activity): InvoiceItem
    {
        $this->activity = $activity;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): InvoiceItem
    {
        $this->project = $project;

        return $this;
    }

    public function isFixedRate(): bool
    {
        return null !== $this->getFixedRate();
    }

    public function getFixedRate(): ?float
    {
        return $this->fixedRate;
    }

    public function setFixedRate(?float $fixedRate): InvoiceItem
    {
        $this->fixedRate = $fixedRate;

        return $this;
    }

    public function getHourlyRate(): ?float
    {
        return $this->hourlyRate;
    }

    public function setHourlyRate(float $hourlyRate): InvoiceItem
    {
        $this->hourlyRate = $hourlyRate;

        return $this;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function setRate(float $rate): InvoiceItem
    {
        $this->rate = $rate;

        return $this;
    }

    public function getInternalRate(): float
    {
        return $this->rateInternal;
    }

    public function setInternalRate(float $rateInternal): InvoiceItem
    {
        $this->rateInternal = $rateInternal;

        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): InvoiceItem
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): InvoiceItem
    {
        $this->description = $description;

        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): InvoiceItem
    {
        $this->duration = $duration;

        return $this;
    }

    public function getBegin(): ?\DateTime
    {
        return $this->begin;
    }

    public function setBegin(\DateTime $begin): InvoiceItem
    {
        $this->begin = $begin;

        return $this;
    }

    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }

    public function setEnd(?\DateTime $end): InvoiceItem
    {
        $this->end = $end;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): InvoiceItem
    {
        $this->user = $user;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): InvoiceItem
    {
        $this->type = $type;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): InvoiceItem
    {
        $this->category = $category;

        return $this;
    }
}
