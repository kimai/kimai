<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Repository\TimesheetRepository;
use App\Widget\WidgetException;
use App\Widget\WidgetInterface;

abstract class AbstractSimpleStatisticChart extends AbstractWidgetType
{
    /**
     * @var TimesheetRepository::STATS_QUERY_*
     */
    private string $query;
    /**
     * @var string|\DateTime|null
     */
    private $begin;
    /**
     * @var string|\DateTime|null
     */
    private $end;
    private bool $queryWithUser = false;

    public function __construct(private TimesheetRepository $repository)
    {
    }

    public function getWidth(): int
    {
        return WidgetInterface::WIDTH_SMALL;
    }

    public function getHeight(): int
    {
        return WidgetInterface::HEIGHT_SMALL;
    }

    /**
     * @param TimesheetRepository::STATS_QUERY_* $query
     * @return void
     */
    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    public function setBegin(null|string|\DateTime $begin): self
    {
        $this->begin = $begin;

        return $this;
    }

    public function getBegin(): ?\DateTime
    {
        if ($this->begin === null) {
            return null;
        }

        if ($this->begin instanceof \DateTime) {
            return $this->begin;
        }

        return new \DateTime($this->begin, $this->getTimezone());
    }

    public function setEnd(null|string|\DateTime $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function getEnd(): ?\DateTime
    {
        if ($this->end === null) {
            return null;
        }

        if ($this->end instanceof \DateTime) {
            return $this->end;
        }

        return new \DateTime($this->end, $this->getTimezone());
    }

    public function setQueryWithUser(bool $queryWithUser): self
    {
        $this->queryWithUser = $queryWithUser;

        return $this;
    }

    public function getTimezone(): \DateTimeZone
    {
        $timezone = date_default_timezone_get();
        if (null !== $this->getUser()) {
            $timezone = $this->getUser()->getTimezone();
        }

        return new \DateTimeZone($timezone);
    }

    /**
     * @param array $options
     * @return mixed|null
     * @throws WidgetException
     */
    public function getData(array $options = []): mixed
    {
        try {
            $user = null;
            if (true === $this->queryWithUser) {
                $user = $this->getUser();
            }

            return $this->repository->getStatistic($this->query, $this->getBegin(), $this->getEnd(), $user);
        } catch (\Exception $ex) {
            throw new WidgetException(
                'Failed loading widget data: ' . $ex->getMessage()
            );
        }
    }

    public function getTemplateName(): string
    {
        $name = (new \ReflectionClass($this))->getShortName();

        return sprintf('widget/widget-%s.html.twig', strtolower($name));
    }
}
