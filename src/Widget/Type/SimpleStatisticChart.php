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

class SimpleStatisticChart extends SimpleWidget implements UserWidget
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
     * @var string|\DateTime
     */
    protected $begin;
    /**
     * @var string|\DateTime
     */
    protected $end;
    /**
     * @var User|null
     */
    protected $user;
    /**
     * @var bool
     */
    private $queryWithUser = false;

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

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function setData($data): AbstractWidgetType
    {
        throw new \InvalidArgumentException('Cannot set data on instances of SimpleStatisticChart');
    }

    public function setQueryWithUser(bool $queryWithUser): SimpleStatisticChart
    {
        $this->queryWithUser = $queryWithUser;

        return $this;
    }

    public function getTimezone(): \DateTimeZone
    {
        $timezone = date_default_timezone_get();
        if (null !== $this->user) {
            $timezone = $this->user->getTimezone();
        }

        return new \DateTimeZone($timezone);
    }

    /**
     * @param array $options
     * @return mixed|null
     * @throws WidgetException
     */
    public function getData(array $options = [])
    {
        $timezone = $this->getTimezone();

        $begin = $this->begin;
        $end = $this->end;

        if (!empty($begin) && \is_string($begin)) {
            $this->begin = new \DateTime($begin, $timezone);
        }

        if (!empty($end) && \is_string($end)) {
            $this->end = new \DateTime($end, $timezone);
        }

        try {
            $user = null;
            if (true === $this->queryWithUser) {
                $user = $this->user;
            }

            return $this->repository->getStatistic($this->query, $this->begin, $this->end, $user);
        } catch (\Exception $ex) {
            throw new WidgetException(
                'Failed loading widget data: ' . $ex->getMessage()
            );
        }
    }
}
