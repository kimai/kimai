<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use App\Entity\User;
use App\Security\CurrentUser;

class UserDateTimeFactory
{
    /**
     * @var \DateTimeZone
     */
    protected $timezone;

    /**
     * @param CurrentUser $user
     */
    public function __construct(CurrentUser $user)
    {
        $timezone = date_default_timezone_get();

        $user = $user->getUser();
        if ($user instanceof User && null !== $user->getPreferenceValue('timezone')) {
            $timezone = $user->getPreferenceValue('timezone');
        }

        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * @return \DateTimeZone
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @return \DateTime
     */
    public function getStartOfMonth()
    {
        $date = $this->createDateTime('first day of this month');
        $date->setTime(0, 0, 0);

        return $date;
    }

    /**
     * @return \DateTime
     */
    public function getEndOfMonth()
    {
        $date = $this->createDateTime('last day of this month');
        $date->setTime(23, 59, 59);

        return $date;
    }

    /**
     * @param string $datetime
     * @return \DateTime
     */
    public function createDateTime(string $datetime = 'now')
    {
        $date = new \DateTime($datetime, $this->timezone);

        return $date;
    }

    /**
     * @param string $format
     * @param null|string $datetime
     * @return bool|\DateTime
     */
    public function createDateTimeFromFormat(string $format, ?string $datetime = 'now')
    {
        $date = \DateTime::createFromFormat($format, $datetime, $this->timezone);

        return $date;
    }
}
