<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\User;
use App\Widget\Type\SimpleStatistic;
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
    protected $repository;
    /**
     * @var array
     */
    protected $widgets = [];
    /**
     * @var array
     */
    protected $definitions = [];

    /**
     * @param TimesheetRepository $repository
     * @param array $widgets
     */
    public function __construct(TimesheetRepository $repository, array $widgets)
    {
        $this->repository = $repository;
        $defaults = $this->getDefaultWidgets();
        $this->definitions = array_merge($defaults, $widgets);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id)
    {
        return isset($this->definitions[$id]) || isset($this->widgets[$id]);
    }

    public function registerWidget(WidgetInterface $widget): WidgetRepository
    {
        if (!empty($widget->getId())) {
            $this->widgets[$widget->getId()] = $widget;
        }

        return $this;
    }

    public function get(string $id, ?User $user): WidgetInterface
    {
        if (!$this->has($id)) {
            throw new \InvalidArgumentException('Cannot find widget: ' . $id);
        }

        if (isset($this->widgets[$id])) {
            return $this->widgets[$id];
        }

        // this code should ONLY be reached for internal (pre-registered) widgets
        $this->registerWidget($this->create($id, $this->definitions[$id], $user));

        return $this->widgets[$id];
    }

    protected function create(string $name, array $widget, ?User $user): WidgetInterface
    {
        $begin = !empty($widget['begin']) ? new \DateTime($widget['begin']) : null;
        $end = !empty($widget['end']) ? new \DateTime($widget['end']) : null;
        $theUser = $widget['user'] ? $user : null;
        if (!isset($widget['type'])) {
            @trigger_error('Using a widget definition without a "type" (counter, more) is deprecated', E_USER_DEPRECATED);
            $widget['type'] = 'counter';
        }

        $widgetClassName = '\\App\\Widget\\Type\\' . ucfirst($widget['type']);
        if (!class_exists($widgetClassName)) {
            throw new WidgetException(sprintf('Unknown widget type "%s"', $widgetClassName));
        }

        $model = new \ReflectionClass($widgetClassName);
        if (!$model->isSubclassOf(SimpleStatistic::class)) {
            throw new WidgetException(sprintf('Invalid widget type "%s" does not extend SimpleStatistic', $widgetClassName));
        }

        $data = $this->repository->getStatistic($widget['query'], $begin, $end, $theUser);

        /** @var SimpleStatistic $model */
        $model = new $widgetClassName();
        $model
            ->setId($name)
            ->setTitle($widget['title'])
            ->setData($data);

        $options = [];
        if ($widget['query'] == TimesheetRepository::STATS_QUERY_DURATION) {
            $options['dataType'] = SimpleStatistic::DATA_TYPE_DURATION;
        } elseif ($widget['query'] == TimesheetRepository::STATS_QUERY_RATE) {
            $options['dataType'] = SimpleStatistic::DATA_TYPE_MONEY;
        } else {
            $options['dataType'] = 'int';
        }
        unset($widget['query']);

        if (isset($widget['color'])) {
            $options['color'] = $widget['color'];
        }
        if (isset($widget['icon'])) {
            $options['icon'] = $widget['icon'];
        }

        $model->setOptions($options);

        return $model;
    }

    protected function getDefaultWidgets(): array
    {
        return
        [
            'userDurationToday' => [
                'title' => 'stats.durationToday',
                'query' => 'duration',
                'user' => true,
                'begin' => '00:00:00',
                'end' => '23:59:59',
                'icon' => 'duration',
                'color' => 'green',
                'type' => 'counter'
            ],
            'userDurationWeek' => [
                'title' => 'stats.durationWeek',
                'query' => 'duration',
                'user' => true,
                'begin' => 'monday this week 00:00:00',
                'end' => 'sunday this week 23:59:59',
                'icon' => 'duration',
                'color' => 'blue',
                'type' => 'counter'
            ],
            'userDurationMonth' => [
                'title' => 'stats.durationMonth',
                'query' => 'duration',
                'user' => true,
                'begin' => 'first day of this month 00:00:00',
                'end' => 'last day of this month 23:59:59',
                'icon' => 'duration',
                'color' => 'purple',
                'type' => 'counter'
            ],
            'userDurationYear' => [
                'title' => 'stats.durationYear',
                'query' => 'duration',
                'user' => true,
                'begin' => '01 january this year 00:00:00',
                'end' => '31 december this year 23:59:59',
                'icon' => 'duration',
                'color' => 'yellow',
                'type' => 'counter'
            ],
            'userDurationTotal' => [
                'title' => 'stats.durationTotal',
                'query' => 'duration',
                'user' => true,
                'icon' => 'duration',
                'color' => 'red',
                'type' => 'counter'
            ],
            'userAmountToday' => [
                'title' => 'stats.amountToday',
                'query' => 'rate',
                'user' => true,
                'begin' => '00:00:00',
                'end' => '23:59:59',
                'icon' => 'money',
                'color' => 'green',
                'type' => 'counter'
            ],
            'userAmountWeek' => [
                'title' => 'stats.amountWeek',
                'query' => 'rate',
                'user' => true,
                'begin' => 'monday this week 00:00:00',
                'end' => 'sunday this week 23:59:59',
                'icon' => 'money',
                'color' => 'blue',
                'type' => 'counter'
            ],
            'userAmountMonth' => [
                'title' => 'stats.amountMonth',
                'query' => 'rate',
                'user' => true,
                'begin' => 'first day of this month 00:00:00',
                'end' => 'last day of this month 23:59:59',
                'icon' => 'money',
                'color' => 'purple',
                'type' => 'counter'
            ],
            'userAmountYear' => [
                'title' => 'stats.amountYear',
                'query' => 'rate',
                'user' => true,
                'begin' => '01 january this year 00:00:00',
                'end' => '31 december this year 23:59:59',
                'icon' => 'money',
                'color' => 'yellow',
                'type' => 'counter'
            ],
            'userAmountTotal' => [
                'title' => 'stats.amountTotal',
                'query' => 'rate',
                'user' => true,
                'icon' => 'money',
                'color' => 'red',
                'type' => 'counter'
            ],
            'durationToday' => [
                'title' => 'stats.durationToday',
                'query' => 'duration',
                'begin' => '00:00:00',
                'end' => '23:59:59',
                'icon' => 'duration',
                'color' => 'green',
                'user' => false,
                'type' => 'counter'
            ],
            'durationWeek' => [
                'title' => 'stats.durationWeek',
                'query' => 'duration',
                'begin' => 'monday this week 00:00:00',
                'end' => 'sunday this week 23:59:59',
                'icon' => 'duration',
                'color' => 'blue',
                'user' => false,
                'type' => 'counter'
            ],
            'durationMonth' => [
                'title' => 'stats.durationMonth',
                'query' => 'duration',
                'begin' => 'first day of this month 00:00:00',
                'end' => 'last day of this month 23:59:59',
                'icon' => 'duration',
                'color' => 'purple',
                'user' => false,
                'type' => 'counter'
            ],
            'durationYear' => [
                'title' => 'stats.durationYear',
                'query' => 'duration',
                'begin' => '01 january this year 00:00:00',
                'end' => '31 december this year 23:59:59',
                'icon' => 'duration',
                'color' => 'yellow',
                'user' => false,
                'type' => 'counter'
            ],
            'durationTotal' => [
                'title' => 'stats.durationTotal',
                'query' => 'duration',
                'icon' => 'duration',
                'color' => 'red',
                'user' => false,
                'type' => 'counter'
            ],
            'amountToday' => [
                'title' => 'stats.amountToday',
                'query' => 'rate',
                'begin' => '00:00:00',
                'end' => '23:59:59',
                'icon' => 'money',
                'color' => 'green',
                'user' => false,
                'type' => 'counter'
            ],
            'amountWeek' => [
                'title' => 'stats.amountWeek',
                'query' => 'rate',
                'begin' => 'monday this week 00:00:00',
                'end' => 'sunday this week 23:59:59',
                'icon' => 'money',
                'color' => 'blue',
                'user' => false,
                'type' => 'counter'
            ],
            'amountMonth' => [
                'title' => 'stats.amountMonth',
                'query' => 'rate',
                'begin' => 'first day of this month 00:00:00',
                'end' => 'last day of this month 23:59:59',
                'icon' => 'money',
                'color' => 'purple',
                'user' => false,
                'type' => 'counter'
            ],
            'amountYear' => [
                'title' => 'stats.amountYear',
                'query' => 'rate',
                'begin' => '01 january this year 00:00:00',
                'end' => '31 december this year 23:59:59',
                'icon' => 'money',
                'color' => 'yellow',
                'user' => false,
                'type' => 'counter'
            ],
            'amountTotal' => [
                'title' => 'stats.amountTotal',
                'query' => 'rate',
                'icon' => 'money',
                'color' => 'red',
                'user' => false,
                'type' => 'counter'
            ],
            'activeUsersToday' => [
                'title' => 'stats.userActiveToday',
                'query' => 'users',
                'begin' => '00:00:00',
                'end' => '23:59:59',
                'icon' => 'user',
                'color' => 'green',
                'user' => false,
                'type' => 'counter'
            ],
            'activeUsersWeek' => [
                'title' => 'stats.userActiveWeek',
                'query' => 'users',
                'begin' => 'monday this week 00:00:00',
                'end' => 'sunday this week 23:59:59',
                'icon' => 'user',
                'color' => 'blue',
                'user' => false,
                'type' => 'counter'
            ],
            'activeUsersMonth' => [
                'title' => 'stats.userActiveMonth',
                'query' => 'users',
                'begin' => 'first day of this month 00:00:00',
                'end' => 'last day of this month 23:59:59',
                'icon' => 'user',
                'color' => 'purple',
                'user' => false,
                'type' => 'counter'
            ],
            'activeUsersYear' => [
                'title' => 'stats.userActiveYear',
                'query' => 'users',
                'begin' => '01 january this year 00:00:00',
                'end' => '31 december this year 23:59:59',
                'icon' => 'user',
                'color' => 'yellow',
                'user' => false,
                'type' => 'counter'
            ],
            'activeUsersTotal' => [
                'title' => 'stats.userActiveTotal',
                'query' => 'users',
                'icon' => 'user',
                'color' => 'red',
                'user' => false,
                'type' => 'counter'
            ],
            'activeRecordings' => [
                'title' => 'stats.activeRecordings',
                'query' => 'active',
                'icon' => 'duration',
                'color' => 'red',
                'user' => false,
                'type' => 'counter'
            ],
            'userRecapThisYear' => [
                'title' => 'stats.yourWorkingHours',
                'query' => 'monthly',
                'user' => true,
                'begin' => '01 january this year 00:00:00',
                'end' => '31 december this year 23:59:59',
                'color' => '#3b8bba|rgba(0,115,183,0.6)',
                'icon' => '',
                'type' => 'yearChart'
            ],
            'userRecapLastYear' => [
                'title' => 'stats.yourWorkingHours',
                'query' => 'monthly',
                'user' => true,
                'begin' => '01 january last year 00:00:00',
                'end' => '31 december last year 23:59:59',
                'color' => '#3b8bba|rgba(0,115,183,0.6)',
                'icon' => '',
                'type' => 'yearChart'
            ],
            'userRecapTwoYears' => [
                'title' => 'stats.yourWorkingHours',
                'query' => 'monthly',
                'user' => true,
                'begin' => '01 january last year 00:00:00',
                'end' => '31 december this year 23:59:59',
                'color' => '#3b8bba|rgba(0,115,183,0.6);#c1c7d1|rgb(210,214,222,0.9)',
                'icon' => '',
                'type' => 'yearChart'
            ],
            'userRecapThreeYears' => [
                'title' => 'stats.yourWorkingHours',
                'query' => 'monthly',
                'user' => true,
                'begin' => '2 years ago first day of january 00:00:00',
                'end' => 'this year last day of december 23:59:59',
                'color' => '#3b8bba|rgba(0,115,183,0.6);#c1c7d1|rgb(210,214,222,0.9);#ccc|rgb(233,233,233)',
                'icon' => '',
                'type' => 'yearChart'
            ],
        ];
    }
}
