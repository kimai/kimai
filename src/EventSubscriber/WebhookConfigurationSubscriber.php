<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Event\SystemConfigurationEvent;
use App\Form\Model\Configuration;
use App\Form\Model\SystemConfiguration;
use App\Form\Type\WebhookEndpointsType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class WebhookConfigurationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigurationEvent::class => ['onSystemConfiguration', 100],
        ];
    }

    public function onSystemConfiguration(SystemConfigurationEvent $event): void
    {
        $event->addConfiguration(
            (new SystemConfiguration('webhook'))
                ->setTranslationDomain('system-configuration')
                ->setConfiguration([
                    (new Configuration('webhook.endpoints'))
                        ->setLabel('webhook.endpoints')
                        ->setRequired(false)
                        ->setType(WebhookEndpointsType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setOptions([
                            'help' => 'help.webhook.endpoints',
                            'help_html' => true,
                        ]),
                ])
        );
    }
}
