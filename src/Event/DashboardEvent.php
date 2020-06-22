<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\User;
use App\Widget\WidgetContainerInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class DashboardEvent extends Event
{
    /**
     * @deprecated since 1.4, will be removed with 2.0
     */
    public const DASHBOARD = DashboardEvent::class;

    /**
     * @var User
     */
    protected $user;
    /**
     * @var WidgetContainerInterface[]
     */
    protected $widgetRows = [];

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function addSection(WidgetContainerInterface $container): DashboardEvent
    {
        $this->widgetRows[] = $container;

        return $this;
    }

    /**
     * @return WidgetContainerInterface[]
     */
    public function getSections(): array
    {
        return $this->widgetRows;
    }
}
