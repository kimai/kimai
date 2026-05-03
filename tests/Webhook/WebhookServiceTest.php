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
use App\Serializer\SerializerInterface;
use App\Webhook\WebhookService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(WebhookService::class)]
class WebhookServiceTest extends TestCase
{
    /**
     * @param array<string, mixed> $settings
     */
    private function createService(
        array $settings = [],
        ?MessageBusInterface $bus = null,
        ?SerializerInterface $serializer = null,
        ?LoggerInterface $logger = null,
    ): WebhookService {
        $configLoader = $this->createMock(ConfigLoaderInterface::class);
        $configLoader->method('getConfigurations')->willReturn([]);

        $systemConfig = new SystemConfiguration($configLoader, $settings);

        if ($serializer === null) {
            $serializer = $this->createMock(SerializerInterface::class);
            $serializer->method('toArray')->willReturn(['id' => 1, 'name' => 'test']);
        }

        if ($bus === null) {
            $bus = $this->createMock(MessageBusInterface::class);
            $bus->method('dispatch')->willReturn(new Envelope(new \stdClass()));
        }

        $logger ??= new NullLogger();

        return new WebhookService($systemConfig, $serializer, $bus, $logger);
    }

    /**
     * @param list<array{url: string, secret?: string, events: list<string>}> $endpoints
     * @return array<string, mixed>
     */
    private function settings(array $endpoints, int $max = 10): array
    {
        return [
            'webhook.endpoints' => json_encode($endpoints, \JSON_THROW_ON_ERROR),
            'webhook.max_endpoints' => $max,
            'webhook.allow_private_network' => false,
        ];
    }

    public function testIsConfiguredReturnsFalseWhenEmpty(): void
    {
        $service = $this->createService($this->settings([]));

        self::assertFalse($service->isConfigured());
    }

    public function testIsConfiguredReturnsFalseWhenMalformedJson(): void
    {
        $service = $this->createService([
            'webhook.endpoints' => 'not valid json',
            'webhook.max_endpoints' => 10,
        ]);

        self::assertFalse($service->isConfigured());
    }

    public function testIsConfiguredReturnsTrueWhenConfigured(): void
    {
        $service = $this->createService($this->settings([
            ['url' => 'https://example.com/webhook', 'secret' => 'secret', 'events' => ['timesheet']],
        ]));

        self::assertTrue($service->isConfigured());
    }

    public function testTriggerDoesNothingWhenNotConfigured(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects(self::never())->method('toArray');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        $service = $this->createService($this->settings([]), $bus, $serializer);
        $service->trigger('timesheet.created', new \stdClass());
    }

    public function testTriggerDoesNothingWhenEntityTypeNotSubscribed(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects(self::never())->method('toArray');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        $service = $this->createService(
            $this->settings([
                ['url' => 'https://a.example.com', 'secret' => 's', 'events' => ['invoice']],
            ]),
            $bus,
            $serializer,
        );
        $service->trigger('timesheet.created', new \stdClass());
    }

    public function testTriggerDispatchesOneMessagePerMatchingSignedEndpoint(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects(self::once())->method('toArray')->willReturn(['id' => 1]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::exactly(2))->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $service = $this->createService(
            $this->settings([
                ['url' => 'https://a.example.com', 'secret' => 'a', 'events' => ['timesheet.created']],
                ['url' => 'https://b.example.com', 'secret' => 'b', 'events' => ['timesheet.created', 'invoice']],
            ]),
            $bus,
            $serializer,
        );
        $service->trigger('timesheet.created', new \stdClass());
    }

    public function testTriggerMixesSignedAndUnsigned(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $service = $this->createService(
            $this->settings([
                ['url' => 'https://signed.example.com', 'secret' => 'shhh', 'events' => ['timesheet.created']],
            ]),
            $bus,
        );
        $service->trigger('timesheet.created', new \stdClass());
    }
}
