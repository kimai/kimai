<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use KevinPapst\TablerBundle\Event\NotificationEvent;
use KevinPapst\TablerBundle\Helper\Constants as ThemeConstants;
use KevinPapst\TablerBundle\Model\NotificationModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * FIXME not used currently
 * @internal
 */
class NotificationsSubscriber implements EventSubscriberInterface
{
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            NotificationEvent::class => ['onNotificationEvent', 100],
        ];
    }

    public function onNotificationEvent(NotificationEvent $event): void
    {
        $model = new NotificationModel('Test', 'Test', ThemeConstants::TYPE_INFO);
        $model->setUrl($this->urlGenerator->generate('dashboard'));
        $event->addNotification($model);

        $model = new NotificationModel('Foo Bar', 'Foo Bar', ThemeConstants::TYPE_WARNING);
        $model->setUrl($this->urlGenerator->generate('timesheet'));
        $event->addNotification($model);

        $model = new NotificationModel('NOPE', 'NOPE', ThemeConstants::TYPE_ERROR);
        $event->addNotification($model);

        $model = new NotificationModel('Hello', 'Hello', ThemeConstants::TYPE_SUCCESS);
        $event->addNotification($model);
    }
}
