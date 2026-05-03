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
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Messenger\SendWebhookMessage;

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

    private function spyLogger(): SpyLogger
    {
        return new SpyLogger();
    }

    public function testResetClearsCachedEndpoints(): void
    {
        $configLoader = $this->createMock(ConfigLoaderInterface::class);
        $configLoader->method('getConfigurations')->willReturn([]);

        $systemConfig = new SystemConfiguration($configLoader, $this->settings([
            ['url' => 'https://a.example.com', 'secret' => 's', 'events' => ['timesheet']],
        ]));

        $service = new WebhookService(
            $systemConfig,
            $this->createMock(SerializerInterface::class),
            $this->createMock(MessageBusInterface::class),
            new NullLogger(),
        );

        self::assertTrue($service->isConfigured());

        // Mutate underlying config; without reset() the cached value still wins.
        $systemConfig->set('webhook.endpoints', '[]');
        self::assertTrue($service->isConfigured(), 'cached endpoints should survive until reset');

        $service->reset();
        self::assertFalse($service->isConfigured(), 'reset must invalidate the cached endpoints');
    }

    public function testParseEndpointsWarnsOnInvalidJson(): void
    {
        $logger = $this->spyLogger();
        $service = $this->createService(
            ['webhook.endpoints' => 'not valid json', 'webhook.max_endpoints' => 10],
            null,
            null,
            $logger,
        );

        self::assertFalse($service->isConfigured());

        $warnings = array_filter($logger->records, fn ($r) => $r['level'] === 'warning');
        self::assertNotEmpty($warnings);
        self::assertStringContainsString('Failed to parse webhook.endpoints JSON', reset($warnings)['message']);
    }

    public function testParseEndpointsWarnsOnNonArrayJsonRoot(): void
    {
        $logger = $this->spyLogger();
        $service = $this->createService(
            ['webhook.endpoints' => '42', 'webhook.max_endpoints' => 10],
            null,
            null,
            $logger,
        );

        self::assertFalse($service->isConfigured());

        $warnings = array_filter($logger->records, fn ($r) => $r['level'] === 'warning');
        self::assertNotEmpty($warnings);
        self::assertStringContainsString('not a JSON array', reset($warnings)['message']);
    }

    public function testParseEndpointsAcceptsWhitespaceOrEmptyArrayLiteral(): void
    {
        $logger = $this->spyLogger();

        foreach (['', '   ', '[]'] as $raw) {
            $service = $this->createService(
                ['webhook.endpoints' => $raw, 'webhook.max_endpoints' => 10],
                null,
                null,
                $logger,
            );
            self::assertFalse($service->isConfigured(), 'raw value ' . var_export($raw, true) . ' should be treated as empty');
        }

        self::assertSame([], $logger->records, 'no warnings should be logged for empty inputs');
    }

    public function testParseEndpointsDropsMalformedEntriesAndWarnsOnce(): void
    {
        $logger = $this->spyLogger();
        $raw = json_encode([
            'string-instead-of-array',
            ['url' => '', 'events' => ['timesheet']],            // empty url
            ['url' => 'https://no-events.example.com'],          // missing events
            ['url' => 'https://bad-events.example.com', 'events' => 'not-array'],
            ['url' => 'https://ok.example.com', 'events' => ['timesheet']],
        ], \JSON_THROW_ON_ERROR);

        $service = $this->createService(
            ['webhook.endpoints' => $raw, 'webhook.max_endpoints' => 10],
            null,
            null,
            $logger,
        );

        self::assertTrue($service->isConfigured(), 'one valid entry should survive');

        $warnings = array_values(array_filter($logger->records, fn ($r) => $r['level'] === 'warning'));
        self::assertCount(1, $warnings);
        self::assertStringContainsString('Dropped {count} malformed', $warnings[0]['message']);
        self::assertSame(4, $warnings[0]['context']['count']);
    }

    public function testParseEndpointsSlicesAtMaxAndWarns(): void
    {
        $endpoints = [];
        for ($i = 0; $i < 5; $i++) {
            $endpoints[] = ['url' => 'https://e' . $i . '.example.com', 'secret' => 's', 'events' => ['timesheet.created']];
        }

        $logger = $this->spyLogger();
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::exactly(2))->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $service = $this->createService(
            $this->settings($endpoints, max: 2),
            $bus,
            null,
            $logger,
        );
        $service->trigger('timesheet.created', new \stdClass());

        $warnings = array_values(array_filter($logger->records, fn ($r) => $r['level'] === 'warning'));
        self::assertCount(1, $warnings);
        self::assertSame(5, $warnings[0]['context']['count']);
        self::assertSame(2, $warnings[0]['context']['max']);
    }

    public function testParseEndpointsCachesOnRepeatedAccess(): void
    {
        // A malformed JSON should produce the warning *once* across multiple calls,
        // proving the result is cached.
        $logger = $this->spyLogger();
        $service = $this->createService(
            ['webhook.endpoints' => 'broken{', 'webhook.max_endpoints' => 10],
            null,
            null,
            $logger,
        );

        $service->isConfigured();
        $service->isConfigured();
        $service->trigger('timesheet.created', new \stdClass());

        $warnings = array_filter($logger->records, fn ($r) => $r['level'] === 'warning');
        self::assertCount(1, $warnings, 'expected the parse warning to be emitted only once thanks to caching');
    }

    public function testTriggerSerializesPayloadWithEntityGroups(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects(self::once())
            ->method('toArray')
            ->with(self::isInstanceOf(\stdClass::class), ['groups' => ['Default', 'Entity', 'Expanded']])
            ->willReturn(['id' => 1]);

        $service = $this->createService(
            $this->settings([
                ['url' => 'https://a.example.com', 'secret' => 's', 'events' => ['timesheet.created']],
            ]),
            null,
            $serializer,
        );
        $service->trigger('timesheet.created', new \stdClass());
    }

    public function testTriggerDispatchesSendWebhookMessageWithExpectedPayload(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('toArray')->willReturn(['id' => 1, 'name' => 'demo']);

        /** @var list<SendWebhookMessage> $captured */
        $captured = [];
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(function (object $message) use (&$captured): Envelope {
            self::assertInstanceOf(SendWebhookMessage::class, $message);
            $captured[] = $message;

            return new Envelope($message);
        });

        $service = $this->createService(
            $this->settings([
                ['url' => 'https://a.example.com', 'secret' => 'sa', 'events' => ['timesheet.created']],
            ]),
            $bus,
            $serializer,
        );
        $service->trigger('timesheet.created', new \stdClass());

        self::assertCount(1, $captured);
        $message = $captured[0];

        self::assertSame('https://a.example.com', $message->getSubscriber()->getUrl());
        self::assertSame('sa', $message->getSubscriber()->getSecret());

        $event = $message->getEvent();
        self::assertInstanceOf(RemoteEvent::class, $event);
        self::assertSame('timesheet.created', $event->getName());
        self::assertNotSame('', $event->getId());
        self::assertSame($event->getId(), $event->getPayload()['id']);
        self::assertSame('timesheet.created', $event->getPayload()['event']);
        self::assertSame(['id' => 1, 'name' => 'demo'], $event->getPayload()['data']);
    }

    public function testTriggerSharesEventIdAcrossEndpointsButPerInvocationUnique(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('toArray')->willReturn([]);

        /** @var list<SendWebhookMessage> $captured */
        $captured = [];
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(function (object $message) use (&$captured): Envelope {
            $captured[] = $message;

            return new Envelope($message);
        });

        $service = $this->createService(
            $this->settings([
                ['url' => 'https://a.example.com', 'secret' => 'a', 'events' => ['timesheet.created']],
                ['url' => 'https://b.example.com', 'secret' => 'b', 'events' => ['timesheet.created']],
            ]),
            $bus,
            $serializer,
        );

        $service->trigger('timesheet.created', new \stdClass());
        $service->trigger('timesheet.created', new \stdClass());

        self::assertCount(4, $captured);
        // First two messages share the same event id, second pair share a different one.
        self::assertInstanceOf(SendWebhookMessage::class, $captured[0]);
        self::assertInstanceOf(SendWebhookMessage::class, $captured[1]);
        self::assertInstanceOf(SendWebhookMessage::class, $captured[2]);
        self::assertInstanceOf(SendWebhookMessage::class, $captured[3]);
        self::assertSame($captured[0]->getEvent()->getId(), $captured[1]->getEvent()->getId());
        self::assertSame($captured[2]->getEvent()->getId(), $captured[3]->getEvent()->getId());
        self::assertNotSame($captured[0]->getEvent()->getId(), $captured[2]->getEvent()->getId());
    }

    public function testTriggerFiltersNonStringEventEntries(): void
    {
        // events array containing non-string values: the non-strings are filtered,
        // the matching string event still triggers a dispatch.
        $raw = json_encode([
            ['url' => 'https://a.example.com', 'secret' => 's', 'events' => ['timesheet.created', 123, null]],
        ], \JSON_THROW_ON_ERROR);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $service = $this->createService(
            ['webhook.endpoints' => $raw, 'webhook.max_endpoints' => 10],
            $bus,
        );
        $service->trigger('timesheet.created', new \stdClass());
    }
}

class SpyLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
    public array $records = [];

    /**
     * @param string|\Stringable $level
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = ['level' => (string) $level, 'message' => (string) $message, 'context' => $context];
    }
}
