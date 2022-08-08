<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Event\UserRevenueStatisticEvent;
use App\Repository\TimesheetRepository;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

abstract class AbstractUserRevenuePeriod extends AbstractWidget
{
    public function __construct(private TimesheetRepository $repository, private EventDispatcherInterface $dispatcher)
    {
    }

    public function getTitle(): string
    {
        return 'stats.' . lcfirst($this->getId());
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-counter-money.html.twig';
    }

    public function getPermissions(): array
    {
        return ['view_rate_own_timesheet'];
    }

    public function getOptions(array $options = []): array
    {
        return array_merge([
            'icon' => 'money',
        ], parent::getOptions($options));
    }

    protected function getRevenue(?string $begin, ?string $end, array $options = [])
    {
        $user = $this->getUser();
        $timezone = new \DateTimeZone($user->getTimezone());

        if ($begin !== null) {
            $begin = new \DateTime($begin, $timezone);
        }

        if ($end !== null) {
            $end = new \DateTime($end, $timezone);
        }

        $data = $this->repository->getRevenue($begin, $end, $user);

        $event = new UserRevenueStatisticEvent($user, $begin, $end);
        foreach ($data as $row) {
            $event->addRevenue($row->getCurrency(), $row->getAmount());
        }
        $this->dispatcher->dispatch($event);

        return $event->getRevenue();
    }
}
