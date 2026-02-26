<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Webhook;

use App\Configuration\ConfigLoaderInterface;
use App\Configuration\SystemConfiguration;
use App\Entity\WebhookConfiguration;
use App\Serializer\SerializerInterface;
use App\Webhook\WebhookService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(WebhookService::class)]
class WebhookServiceTest extends TestCase
{
    private function createService(array $settings = []): WebhookService
    {
        $configLoader = $this->createMock(ConfigLoaderInterface::class);
        $configLoader->method('getConfigurations')->willReturn([]);

        $systemConfig = new SystemConfiguration($configLoader, $settings);
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('toArray')->willReturn(['id' => 1, 'name' => 'test']);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        return new WebhookService($systemConfig, $serializer, $bus);
    }

    public function testIsConfiguredReturnsFalseWhenEmpty(): void
    {
        $service = $this->createService([
            'webhook.endpoint_url' => '',
            'webhook.secret_token' => '',
        ]);

        self::assertFalse($service->isConfigured());
    }

    public function testIsConfiguredReturnsTrueWhenConfigured(): void
    {
        $service = $this->createService([
            'webhook.endpoint_url' => 'https://example.com/webhook',
            'webhook.secret_token' => 'secret',
        ]);

        self::assertTrue($service->isConfigured());
    }

    public function testFindEventsByNameReturnsEmptyWhenNoUrl(): void
    {
        $service = $this->createService([
            'webhook.endpoint_url' => '',
            'webhook.secret_token' => '',
        ]);

        self::assertEmpty($service->findEventsByName('timesheet.created'));
    }

    public function testFindEventsByNameReturnsEventWhenConfigured(): void
    {
        $service = $this->createService([
            'webhook.endpoint_url' => 'https://example.com/webhook',
            'webhook.secret_token' => 'my-secret',
        ]);

        $events = $service->findEventsByName('timesheet.created');
        self::assertCount(1, $events);

        $webhookEvent = $events[0];
        $configuration = $webhookEvent->getConfiguration();
        self::assertInstanceOf(WebhookConfiguration::class, $configuration);
        self::assertEquals('https://example.com/webhook', $configuration->getUrl());
        self::assertEquals('my-secret', $configuration->getSecret());
    }

    public function testTriggerDoesNothingWhenNoWebhook(): void
    {
        $configLoader = $this->createMock(ConfigLoaderInterface::class);
        $configLoader->method('getConfigurations')->willReturn([]);

        $systemConfig = new SystemConfiguration($configLoader, [
            'webhook.endpoint_url' => '',
            'webhook.secret_token' => '',
        ]);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects(self::never())->method('toArray');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        $service = new WebhookService($systemConfig, $serializer, $bus);
        $service->trigger('timesheet.created', new \stdClass());
    }

    public function testTriggerDispatchesMessageWhenConfigured(): void
    {
        $configLoader = $this->createMock(ConfigLoaderInterface::class);
        $configLoader->method('getConfigurations')->willReturn([]);

        $systemConfig = new SystemConfiguration($configLoader, [
            'webhook.endpoint_url' => 'https://example.com/webhook',
            'webhook.secret_token' => 'secret',
        ]);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects(self::once())->method('toArray')->willReturn(['id' => 1]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $service = new WebhookService($systemConfig, $serializer, $bus);
        $service->trigger('timesheet.created', new \stdClass());
    }
}
