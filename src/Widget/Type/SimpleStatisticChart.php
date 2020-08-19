<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Entity\User;
use App\Repository\TimesheetRepository;
use App\Widget\WidgetException;

class SimpleStatisticChart extends SimpleWidget
{
    /**
     * @var TimesheetRepository
     */
    private $repository;
    /**
     * @var string
     */
    private $query;
    /**
     * @var string
     */
    private $begin;
    /**
     * @var string
     */
    private $end;
    /**
     * @var User
     */
    private $user;

    public function __construct(TimesheetRepository $repository)
    {
        $this->repository = $repository;
    }

    public function setQuery(string $query): SimpleStatisticChart
    {
        $this->query = $query;

        return $this;
    }

    public function setBegin(?string $begin): SimpleStatisticChart
    {
        $this->begin = $begin;

        return $this;
    }

    public function setEnd(?string $end): SimpleStatisticChart
    {
        $this->end = $end;

        return $this;
    }

    public function setUser(User $user): SimpleStatisticChart
    {
        $this->user = $user;

        return $this;
    }

    public function setData($data): AbstractWidgetType
    {
        throw new \InvalidArgumentException('Cannot set data on instances of SimpleStatisticChart');
    }

    /**
     * @param array $options
     * @return mixed|null
     * @throws WidgetException
     */
    public function getData(array $options = [])
    {
        $timezone = date_default_timezone_get();
        if (null !== $this->user) {
            $timezone = $this->user->getTimezone();
        }
        $timezone = new \DateTimeZone($timezone);

        $begin = !empty($this->begin) ? new \DateTime($this->begin, $timezone) : null;
        $end = !empty($this->end) ? new \DateTime($this->end, $timezone) : null;

        try {
            return $this->repository->getStatistic($this->query, $begin, $end, $this->user);
        } catch (\Exception $ex) {
            throw new WidgetException(
                'Failed loading widget data: ' . $ex->getMessage()
            );
        }
    }
}
