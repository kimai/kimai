<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Calendar;

class Config
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getBusinessDays()
    {
        return $this->config['businessHours']['days'];
    }

    /**
     * @return string
     */
    public function getBusinessTimeBegin()
    {
        return $this->config['businessHours']['begin'];
    }

    /**
     * @return string
     */
    public function getBusinessTimeEnd()
    {
        return $this->config['businessHours']['end'];
    }

    /**
     * @return int
     */
    public function getDayLimit()
    {
        return $this->config['day_limit'];
    }

    /**
     * @return bool
     */
    public function isShowWeekNumbers()
    {
        return $this->config['week_numbers'];
    }
}
