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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

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
                    (new Configuration('webhook.endpoint_url'))
                        ->setLabel('webhook.endpoint_url')
                        ->setRequired(false)
                        ->setType(UrlType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setOptions(['help' => 'help.webhook.endpoint_url']),
                    (new Configuration('webhook.secret_token'))
                        ->setLabel('webhook.secret_token')
                        ->setRequired(false)
                        ->setType(PasswordType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setOptions([
                            'help' => 'help.webhook.secret_token',
                            'always_empty' => false,
                        ]),
                    (new Configuration('webhook.events.timesheet'))
                        ->setLabel('webhook.events.timesheet')
                        ->setRequired(false)
                        ->setType(CheckboxType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('webhook.events.customer'))
                        ->setLabel('webhook.events.customer')
                        ->setRequired(false)
                        ->setType(CheckboxType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('webhook.events.project'))
                        ->setLabel('webhook.events.project')
                        ->setRequired(false)
                        ->setType(CheckboxType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('webhook.events.activity'))
                        ->setLabel('webhook.events.activity')
                        ->setRequired(false)
                        ->setType(CheckboxType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('webhook.events.invoice'))
                        ->setLabel('webhook.events.invoice')
                        ->setRequired(false)
                        ->setType(CheckboxType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('webhook.events.user'))
                        ->setLabel('webhook.events.user')
                        ->setRequired(false)
                        ->setType(CheckboxType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('webhook.events.team'))
                        ->setLabel('webhook.events.team')
                        ->setRequired(false)
                        ->setType(CheckboxType::class)
                        ->setTranslationDomain('system-configuration'),
                ])
        );
    }
}
