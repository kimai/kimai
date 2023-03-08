<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;
use App\Widget\WidgetInterface;

final class DashboardSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'dashboard';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        /** @var WidgetInterface[] $widgets */
        $widgets = $payload['widgets'];
        /** @var WidgetInterface[] $available */
        $available = $payload['available'];

        if (!$event->isView('edit')) {
            $event->addConfig($this->path('dashboard_edit'));
        } else {
            $ids = [];

            $event->addAction('save', ['title' => 'action.save', 'onclick' => 'saveDashboard(); return false;', 'icon' => 'save']);

            foreach ($widgets as $widget) {
                $ids[] = $widget->getId();
            }

            foreach ($available as $widget) {
                if ($widget->isInternal()) {
                    continue;
                }

                // prevent to use the same widget multiple times
                if (\in_array($widget->getId(), $ids)) {
                    continue;
                }

                $permissions = $widget->getPermissions();
                if (\count($permissions) > 0) {
                    $allow = false;
                    foreach ($widget->getPermissions() as $permission) {
                        if ($this->isGranted($permission)) {
                            $allow = true;
                        }
                    }

                    if (!$allow) {
                        continue;
                    }
                }

                if (empty($widget->getTitle())) {
                    continue;
                }

                $event->addActionToSubmenu('widget_add', $widget->getId(), ['url' => $this->path('dashboard_add', ['widget' => $widget->getId()]), 'title' => $widget->getTitle(), 'translation_domain' => $widget->getTranslationDomain()]);
            }

            $event->addAction('reset', ['title' => 'action.reset', 'url' => $this->path('dashboard_reset'), 'icon' => 'delete', 'class' => 'confirmation-link', 'attr' => ['data-question' => 'confirm.delete']]);
        }
    }
}
