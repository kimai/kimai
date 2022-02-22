<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Widget\Type\Counter;
use App\Widget\Type\SimpleStatisticChart;
use App\Widget\WidgetException;
use App\Widget\WidgetInterface;

/**
 * @internal
 */
class WidgetRepository
{
    /**
     * @var TimesheetRepository
     */
    private $repository;
    /**
     * @var array
     */
    private $widgets = [];
    /**
     * @var array
     */
    private $definitions;
    /**
     * @var array
     */
    private $customDefinition;

    public function __construct(TimesheetRepository $repository, array $widgets)
    {
        $this->repository = $repository;
        $this->customDefinition = $widgets;
    }

    private function getDefinedWidgets(): array
    {
        if (null === $this->definitions) {
            $this->definitions = array_merge($this->getDefaultWidgets(), $this->customDefinition);
        }

        return $this->definitions;
    }

    public function has(string $id): bool
    {
        return isset($this->getDefinedWidgets()[$id]) || isset($this->widgets[$id]);
    }

    public function registerWidget(WidgetInterface $widget): WidgetRepository
    {
        if (!empty($widget->getId())) {
            $this->widgets[$widget->getId()] = $widget;
        }

        return $this;
    }

    public function get(string $id): WidgetInterface
    {
        if (!$this->has($id)) {
            throw new \InvalidArgumentException(sprintf('Cannot find widget "%s".', $id));
        }

        if (isset($this->widgets[$id])) {
            return $this->widgets[$id];
        }

        // this code should ONLY be reached for internal (pre-registered) widgets
        $this->registerWidget($this->create($id, $this->getDefinedWidgets()[$id]));

        return $this->widgets[$id];
    }

    /**
     * @param string $name
     * @param array $widget
     * @return WidgetInterface
     * @throws WidgetException
     */
    protected function create(string $name, array $widget): WidgetInterface
    {
        if (!isset($widget['type'])) {
            @trigger_error('Using a widget definition without a "type" is deprecated', E_USER_DEPRECATED);
            $widget['type'] = Counter::class;
        }
        $widgetClassName = ucfirst($widget['type']);
        if (!class_exists($widgetClassName)) {
            throw new WidgetException(sprintf('Unknown widget type "%s"', $widgetClassName));
        }

        /** @var SimpleStatisticChart $model */
        $model = new $widgetClassName($this->repository);
        if (!($model instanceof SimpleStatisticChart)) {
            throw new WidgetException(
                sprintf(
                    'Widget type "%s" is not an instance of "%s"',
                    $widgetClassName,
                    SimpleStatisticChart::class
                )
            );
        }

        $model
            ->setQuery($widget['query'])
            ->setBegin($widget['begin'])
            ->setEnd($widget['end'])
            ->setId($name)
            ->setTitle($widget['title'])
        ;

        if ($widget['query'] === TimesheetRepository::STATS_QUERY_DURATION) {
            $model->setOption('dataType', 'duration');
        } elseif ($widget['query'] === TimesheetRepository::STATS_QUERY_RATE) {
            $model->setOption('dataType', 'money');
        } else {
            $model->setOption('dataType', 'int');
        }

        if (isset($widget['user'])) {
            $model->setQueryWithUser((bool) $widget['user']);
        }
        if (isset($widget['color'])) {
            $model->setOption('color', $widget['color']);
        }
        if (isset($widget['icon'])) {
            $model->setOption('icon', $widget['icon']);
        }

        return $model;
    }

    protected function getDefaultWidgets(): array
    {
        return
        [
            'userDurationToday' => [
                'title' => 'stats.durationToday',
                'query' => TimesheetRepository::STATS_QUERY_DURATION,
                'user' => true,
                'begin' => '00:00:00',
                'end' => '23:59:59',
                'icon' => 'duration',
                'color' => 'green',
                'type' => Counter::class,
            ],
            'userDurationWeek' => [
                'title' => 'stats.durationWeek',
                'query' => TimesheetRepository::STATS_QUERY_DURATION,
                'user' => true,
                'begin' => 'monday this week 00:00:00',
                'end' => 'sunday this week 23:59:59',
                'icon' => 'duration',
                'color' => 'blue',
                'type' => Counter::class,
            ],
            'userDurationMonth' => [
                'title' => 'stats.durationMonth',
                'query' => TimesheetRepository::STATS_QUERY_DURATION,
                'user' => true,
                'begin' => 'first day of this month 00:00:00',
                'end' => 'last day of this month 23:59:59',
                'icon' => 'duration',
                'color' => 'purple',
                'type' => Counter::class,
            ],
            'userDurationTotal' => [
                'title' => 'stats.durationTotal',
                'query' => TimesheetRepository::STATS_QUERY_DURATION,
                'user' => true,
                'icon' => 'duration',
                'color' => 'red',
                'type' => Counter::class,
            ],
            'durationToday' => [
                'title' => 'stats.durationToday',
                'query' => TimesheetRepository::STATS_QUERY_DURATION,
                'begin' => '00:00:00',
                'end' => '23:59:59',
                'icon' => 'duration',
                'color' => 'green',
                'user' => false,
                'type' => Counter::class,
            ],
            'durationWeek' => [
                'title' => 'stats.durationWeek',
                'query' => TimesheetRepository::STATS_QUERY_DURATION,
                'begin' => 'monday this week 00:00:00',
                'end' => 'sunday this week 23:59:59',
                'icon' => 'duration',
                'color' => 'blue',
                'user' => false,
                'type' => Counter::class,
            ],
            'durationMonth' => [
                'title' => 'stats.durationMonth',
                'query' => TimesheetRepository::STATS_QUERY_DURATION,
                'begin' => 'first day of this month 00:00:00',
                'end' => 'last day of this month 23:59:59',
                'icon' => 'duration',
                'color' => 'purple',
                'user' => false,
                'type' => Counter::class,
            ],
            'durationTotal' => [
                'title' => 'stats.durationTotal',
                'query' => TimesheetRepository::STATS_QUERY_DURATION,
                'icon' => 'duration',
                'color' => 'red',
                'user' => false,
                'type' => Counter::class,
            ],
            'activeUsersToday' => [
                'title' => 'stats.userActiveToday',
                'query' => TimesheetRepository::STATS_QUERY_USER,
                'begin' => '00:00:00',
                'end' => '23:59:59',
                'icon' => 'user',
                'color' => 'green',
                'user' => false,
                'type' => Counter::class,
            ],
            'activeUsersWeek' => [
                'title' => 'stats.userActiveWeek',
                'query' => TimesheetRepository::STATS_QUERY_USER,
                'begin' => 'monday this week 00:00:00',
                'end' => 'sunday this week 23:59:59',
                'icon' => 'user',
                'color' => 'blue',
                'user' => false,
                'type' => Counter::class,
            ],
            'activeUsersMonth' => [
                'title' => 'stats.userActiveMonth',
                'query' => TimesheetRepository::STATS_QUERY_USER,
                'begin' => 'first day of this month 00:00:00',
                'end' => 'last day of this month 23:59:59',
                'icon' => 'user',
                'color' => 'purple',
                'user' => false,
                'type' => Counter::class,
            ],
            'activeUsersTotal' => [
                'title' => 'stats.userActiveTotal',
                'query' => TimesheetRepository::STATS_QUERY_USER,
                'icon' => 'user',
                'color' => 'red',
                'user' => false,
                'type' => Counter::class,
            ],
            'activeRecordings' => [
                'title' => 'stats.activeRecordings',
                'query' => TimesheetRepository::STATS_QUERY_ACTIVE,
                'icon' => 'duration',
                'color' => 'red',
                'user' => false,
                'type' => Counter::class,
            ],
        ];
    }
}
