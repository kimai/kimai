<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\User;
use App\Model\Widget;

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
     * @param TimesheetRepository $repository
     * @param array $widgets
     */
    public function __construct(TimesheetRepository $repository, array $widgets)
    {
        $this->repository = $repository;
        $this->widgets = $widgets;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name)
    {
        return isset($this->widgets[$name]);
    }

    /**
     * @param string $name
     * @param User|null $user
     * @return Widget
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function get(string $name, ?User $user)
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException('Cannot find widget: ' . $name);
        }

        $widget = $this->widgets[$name];

        $begin = !empty($widget['begin']) ? new \DateTime($widget['begin']) : null;
        $end = !empty($widget['end']) ? new \DateTime($widget['end']) : null;
        $theUser = $widget['user'] ? $user : null;

        $data = $this->repository->getStatistic($widget['query'], $begin, $end, $theUser);

        $model = new Widget($widget['title'], $data);
        $model
            ->setColor($widget['color'])
            ->setIcon($widget['icon'])
            ->setType($widget['type'])
        ;

        if ($widget['query'] == TimesheetRepository::STATS_QUERY_DURATION) {
            $model->setDataType(Widget::DATA_TYPE_DURATION);
        } elseif ($widget['query'] == TimesheetRepository::STATS_QUERY_AMOUNT) {
            $model->setDataType(Widget::DATA_TYPE_MONEY);
        }

        return $model;
    }
}
