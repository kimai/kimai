<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Calendar;

use App\Entity\Tag;
use App\Entity\Timesheet;

class TimesheetEntity
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var \DateTime
     */
    protected $start;
    /**
     * @var \DateTime|null
     */
    protected $end;
    /**
     * @var string
     */
    protected $title;
    /**
     * @var string
     */
    protected $description;
    /**
     * @var string
     */
    protected $customer;
    /**
     * @var string
     */
    protected $project;
    /**
     * @var string
     */
    protected $activity;
    /**
     * @var string|null
     */
    protected $tags;
    /**
     * @var string|null
     */
    protected $borderColor;
    /**
     * @var string|null
     */
    protected $backgroundColor;

    /**
     * @param Timesheet $entry
     * @param bool $useTags
     */
    public function __construct(Timesheet $entry, bool $useTags = false)
    {
        $this->id = $entry->getId();
        $this->start = $entry->getBegin();
        $this->title = $entry->getActivity()->getName();
        $this->description = $entry->getDescription();
        $this->customer = $entry->getProject()->getCustomer()->getName();
        $this->project = $entry->getProject()->getName();
        $this->activity = $entry->getActivity()->getName();
        if (true === $useTags && sizeof($entry->getTags()) > 0) {
            $arr = [];
            /** @var Tag $tag */
            foreach ($entry->getTags() as $tag) {
                array_push($arr, $tag->getTagName());
            }
            $this->tags = implode(', ', $arr);
        }

        if (null === $entry->getEnd()) {
            // TODO move these colors to the controller
            $this->borderColor = '367fa9'; //'#f39c12';
            $this->backgroundColor = '#3c8dbc'; //'#f39c12';
        } else {
            $this->end = $entry->getEnd();
        }
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return TimesheetEntity
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStart(): \DateTime
    {
        return $this->start;
    }

    /**
     * @param \DateTime $start
     * @return TimesheetEntity
     */
    public function setStart(\DateTime $start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }

    /**
     * @param \DateTime|null $end
     * @return TimesheetEntity
     */
    public function setEnd(?\DateTime $end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return TimesheetEntity
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return TimesheetEntity
     */
    public function setDescription(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomer(): string
    {
        return $this->customer;
    }

    /**
     * @param string $customer
     * @return TimesheetEntity
     */
    public function setCustomer(string $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return string
     */
    public function getProject(): string
    {
        return $this->project;
    }

    /**
     * @param string $project
     * @return TimesheetEntity
     */
    public function setProject(string $project)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return string
     */
    public function getActivity(): string
    {
        return $this->activity;
    }

    /**
     * @param string $activity
     * @return TimesheetEntity
     */
    public function setActivity(string $activity)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTags(): ?string
    {
        return $this->tags;
    }

    /**
     * @param string|null $tags
     * @return TimesheetEntity
     */
    public function setTags(?string $tags)
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getBorderColor(): ?string
    {
        return $this->borderColor;
    }

    /**
     * @param null|string $borderColor
     * @return TimesheetEntity
     */
    public function setBorderColor(?string $borderColor)
    {
        $this->borderColor = $borderColor;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    /**
     * @param null|string $backgroundColor
     * @return TimesheetEntity
     */
    public function setBackgroundColor(?string $backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }
}
