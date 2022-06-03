<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class InvoicesSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'invoices';
    }

    public function onActions(PageActionsEvent $event): void
    {
        if ($this->isGranted('system_configuration')) {
            $event->addAction('settings', ['title' => 'settings', 'translation_domain' => 'actions', 'url' => $this->path('system_configuration_section', ['section' => 'invoice']), 'class' => 'modal-ajax-form']);
        }

        $event->addHelp($this->documentationLink('invoices.html'));
    }
}
