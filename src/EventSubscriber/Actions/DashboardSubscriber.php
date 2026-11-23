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

                $event->addActionToSubmenu('widget_add', $widget->getId(), [
                    'url' => '#',
                    'class' => 'api-link',
                    'attr' => [
                        'data-href' => $this->path('post_dashboard_widget', ['widget' => $widget->getId()]),
                        'data-method' => 'POST',
                        'data-event' => 'kimai.dashboardUpdate',
                        'data-msg-error' => 'action.update.error',
                        'data-msg-success' => 'action.update.success',
                    ],
                    'title' => $widget->getTitle(),
                    'translation_domain' => $widget->getTranslationDomain()
                ]);
            }

            $event->addAction('reset', [
                'title' => 'action.reset',
                'url' => '#',
                'icon' => 'delete',
                'class' => 'api-link',
                'attr' => [
                    'data-href' => $this->path('delete_dashboard_widgets'),
                    'data-method' => 'DELETE',
                    'data-event' => 'kimai.dashboardUpdate',
                    'data-question' => 'confirm.delete',
                    'data-msg-error' => 'action.delete.error',
                    'data-msg-success' => 'action.delete.success',
                ]
            ]);
        }
    }
}
