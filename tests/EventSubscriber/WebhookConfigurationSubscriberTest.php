<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Event\SystemConfigurationEvent;
use App\EventSubscriber\WebhookConfigurationSubscriber;
use App\Form\Type\WebhookEndpointsType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WebhookConfigurationSubscriber::class)]
class WebhookConfigurationSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = WebhookConfigurationSubscriber::getSubscribedEvents();
        self::assertArrayHasKey(SystemConfigurationEvent::class, $events);
        $methodName = $events[SystemConfigurationEvent::class][0];
        self::assertTrue(
            method_exists(WebhookConfigurationSubscriber::class, $methodName),
            \sprintf('Declared handler method %s must exist on the subscriber.', $methodName)
        );
    }

    public function testOnSystemConfiguration(): void
    {
        $sut = new WebhookConfigurationSubscriber();
        $event = new SystemConfigurationEvent([]);
        $sut->onSystemConfiguration($event);

        $configurations = $event->getConfigurations();
        self::assertCount(1, $configurations);

        $config = $configurations[0];
        self::assertEquals('webhook', $config->getSection());
        self::assertEquals('system-configuration', $config->getTranslationDomain());

        $fields = $config->getConfiguration();
        self::assertCount(1, $fields);

        $endpoints = $fields[0];
        self::assertEquals('webhook.endpoints', $endpoints->getName());
        self::assertEquals(WebhookEndpointsType::class, $endpoints->getType());
        self::assertFalse($endpoints->isRequired());

        $options = $endpoints->getOptions();
        self::assertArrayHasKey('help', $options);
        self::assertEquals('help.webhook.endpoints', $options['help']);
        self::assertTrue($options['help_html']);
    }
}
