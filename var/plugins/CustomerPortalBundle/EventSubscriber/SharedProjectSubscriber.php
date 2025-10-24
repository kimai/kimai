<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\CustomerPortalBundle\EventSubscriber;

use App\Event\PageActionsEvent;
use App\EventSubscriber\Actions\AbstractActionsSubscriber;
use KimaiPlugin\CustomerPortalBundle\Entity\SharedProjectTimesheet;

class SharedProjectSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'shared_project';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        if (!\array_key_exists('sharedProject', $payload)) {
            return;
        }

        $sharedProject = $payload['sharedProject'];

        if (!$sharedProject instanceof SharedProjectTimesheet || $sharedProject->getId() === null) {
            return;
        }

        $event->addEdit($this->path('update_shared_project_timesheets', ['sharedProject' => $sharedProject->getId(), 'shareKey' => $sharedProject->getShareKey()]));

        if ($sharedProject->getCustomer() !== null) {
            $event->addAction('customer', ['url' => $this->path('customer_details', ['id' => $sharedProject->getCustomer()->getId()])]);
        } elseif ($sharedProject->getProject() !== null) {
            $event->addAction('project', ['url' => $this->path('project_details', ['id' => $sharedProject->getProject()->getId()])]);
        }

        $event->addDelete($this->path('remove_shared_project_timesheets', ['sharedProject' => $sharedProject->getId(), 'shareKey' => $sharedProject->getShareKey()]), false);
    }
}
