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
        self::assertIsString($methodName);
        self::assertTrue(method_exists(WebhookConfigurationSubscriber::class, $methodName));
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
        self::assertCount(9, $fields); // endpoint_url, secret_token, + 7 event checkboxes

        $fieldNames = array_map(fn ($f) => $f->getName(), $fields);
        self::assertContains('webhook.endpoint_url', $fieldNames);
        self::assertContains('webhook.secret_token', $fieldNames);
        self::assertContains('webhook.events.timesheet', $fieldNames);
        self::assertContains('webhook.events.customer', $fieldNames);
        self::assertContains('webhook.events.project', $fieldNames);
        self::assertContains('webhook.events.activity', $fieldNames);
        self::assertContains('webhook.events.invoice', $fieldNames);
        self::assertContains('webhook.events.user', $fieldNames);
        self::assertContains('webhook.events.team', $fieldNames);
    }
}
