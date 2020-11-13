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
use DateTimeZone;

/**
 * @deprecated will be removed with 2.0
 */
class UserDateTimeFactory extends DateTimeFactory
{
    /**
     * @var CurrentUser
     */
    private $user;
    /**
     * @var bool
     */
    private $initializedFromUser = false;

    public function __construct(CurrentUser $user)
    {
        parent::__construct(null);
        $this->user = $user;
    }

    public function getTimezone(): DateTimeZone
    {
        if ($this->initializedFromUser === false) {
            $timezone = date_default_timezone_get();

            $user = $this->user->getUser();
            if ($user instanceof User) {
                $timezone = $user->getTimezone();
            }

            $timezone = new DateTimeZone($timezone);

            parent::setTimezone($timezone);
            $this->initializedFromUser = true;
        }

        return parent::getTimezone();
    }
}
