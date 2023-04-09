<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;
use App\Twig\Extensions;
use App\WorkingTime\Model\Month;
use App\WorkingTime\Model\Year;

final class ContractMonthSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'contract_month';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        if (!\array_key_exists('year', $payload) || !\array_key_exists('month', $payload)) {
            return;
        }

        $year = $payload['year'];
        $month = $payload['month'];
        $currentUser = $event->getUser();

        if (!$year instanceof Year || !$month instanceof Month || $currentUser === null) {
            return;
        }

        $parameters = ['date' => $month->getMonth()->format(Extensions::REPORT_DATE)];
        if ($this->isGranted('view_other_timesheet')) {
            $parameters['user'] = $year->getUser()->getId();
        }

        $event->addAction('report_user_month', ['url' => $this->path('report_user_month', $parameters), 'translation_domain' => 'reporting', 'title' => 'report_user_month']);
    }
}
