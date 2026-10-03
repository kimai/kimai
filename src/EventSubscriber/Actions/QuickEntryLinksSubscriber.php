<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Entity\User;
use App\Event\PageActionsEvent;
use App\Form\Type\DateRangeType;
use App\Repository\Query\DateRangeInterface;

class QuickEntryLinksSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'quick_entry_links';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();
        if (!\array_key_exists('user', $payload)) {
            return;
        }

        $user = $payload['user'];

        if (!$user instanceof User) {
            return;
        }

        $params = [
            'users' => [$user->getId()],
        ];

        if (\array_key_exists('query', $payload)) {
            $query = $payload['query'];

            if ($query instanceof DateRangeInterface && $query->hasFullDateRange()) {
                $params['daterange'] = DateRangeType::toQueryString($query->getDateRange());
            }
        }

        if ($this->isGranted('view_other_timesheet')) {
            $event->addAction('filter', ['icon' => 'timesheet-team', 'title' => 'timesheet.filter', 'url' => $this->path('admin_timesheet', $params)]);
        }
    }
}
