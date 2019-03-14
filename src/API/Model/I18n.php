<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Model;

class I18n
{
    /**
     * Format used for 'begin' and 'end' in TimesheetEditForm: POST, PATCH
     *
     * @var string
     */
    protected $formDateTime = '';
    /**
     * Format used for Timesheet queries in: GET
     *
     * @var string
     */
    protected $formDate = '';
    /**
     * Format used to display date-time values (see PHP function date_format)
     *
     * @var string
     */
    protected $dateTime = '';
    /**
     * Format used to display date values (see PHP function date_format)
     *
     * @var string
     */
    protected $date = '';
    /**
     * Format used to display times (see PHP function date_format)
     *
     * @var string
     */
    protected $time = '';
    /**
     * Format used to display durations (replace: %h with hours, %m with minutes, %s with seconds)
     *
     * @var string
     */
    protected $duration = '';
    /**
     * Whether a twenty-four hour format is used (true) or 12-hours AM/PM format (false)
     * @var bool
     */
    protected $is24hours = true;

    /**
     * @return string
     */
    public function getFormDateTime(): string
    {
        return $this->formDateTime;
    }

    /**
     * @param string $formDateTime
     * @return I18n
     */
    public function setFormDateTime(string $formDateTime)
    {
        $this->formDateTime = $formDateTime;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormDate(): string
    {
        return $this->formDate;
    }

    /**
     * @param string $formDate
     * @return I18n
     */
    public function setFormDate(string $formDate)
    {
        $this->formDate = $formDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getDateTime(): string
    {
        return $this->dateTime;
    }

    /**
     * @param string $dateTime
     * @return I18n
     */
    public function setDateTime(string $dateTime)
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param string $date
     * @return I18n
     */
    public function setDate(string $date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return string
     */
    public function getDuration(): string
    {
        return $this->duration;
    }

    /**
     * @param string $duration
     * @return I18n
     */
    public function setDuration(string $duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * @return string
     */
    public function getTime(): string
    {
        return $this->time;
    }

    /**
     * @param string $time
     * @return I18n
     */
    public function setTime(string $time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * @return bool
     */
    public function isIs24hours(): bool
    {
        return $this->is24hours;
    }

    /**
     * @param bool $is24hours
     * @return I18n
     */
    public function setIs24hours(bool $is24hours)
    {
        $this->is24hours = $is24hours;

        return $this;
    }
}
