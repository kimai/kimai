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
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
        ?HttpClientInterface $httpClient = null,
        ?\Psr\Log\LoggerInterface $logger = null,
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

        $httpClient ??= new MockHttpClient();
        $logger ??= new NullLogger();

        return new WebhookService($systemConfig, $serializer, $bus, $logger, $httpClient);
    }

    /**
     * @param list<array{url: string, secret?: string, events: list<string>}> $endpoints
     * @return array<string, mixed>
     */
    private function settings(array $endpoints, int $max = 10): array
    {
        // Tests exercise dispatch logic against MockHttpClient; the
        // NoPrivateNetworkHttpClient wrapper resolves DNS and wraps responses
        // in ways that don't compose with MockResponse. We bypass it by
        // enabling allow_private_network — the wrapper itself has its own
        // dedicated coverage in WebhookEndpointTypeTest + a static analysis
        // check at service construction.
        return [
            'webhook.endpoints' => json_encode($endpoints, \JSON_THROW_ON_ERROR),
            'webhook.max_endpoints' => $max,
            'webhook.allow_private_network' => true,
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

    public function testFindEventsByNameReturnsEmptyWhenNoEndpoints(): void
    {
        $service = $this->createService($this->settings([]));

        self::assertEmpty($service->findEventsByName('timesheet.created'));
    }

    public function testFindEventsByNameSkipsEndpointsNotSubscribedToEntity(): void
    {
        $service = $this->createService($this->settings([
            ['url' => 'https://a.example.com', 'secret' => 'a', 'events' => ['invoice']],
            ['url' => 'https://b.example.com', 'secret' => 'b', 'events' => ['timesheet']],
        ]));

        $events = $service->findEventsByName('timesheet.created');
        self::assertCount(1, $events);

        $configuration = $events[0]->getConfiguration();
        self::assertInstanceOf(WebhookConfiguration::class, $configuration);
        self::assertEquals('https://b.example.com', $configuration->getUrl());
        self::assertEquals('b', $configuration->getSecret());
    }

    public function testFindEventsByNameReturnsAllMatchingEndpoints(): void
    {
        $service = $this->createService($this->settings([
            ['url' => 'https://a.example.com', 'secret' => 'a', 'events' => ['timesheet', 'invoice']],
            ['url' => 'https://b.example.com', 'secret' => 'b', 'events' => ['timesheet']],
            ['url' => 'https://c.example.com', 'secret' => 'c', 'events' => ['user']],
        ]));

        $events = $service->findEventsByName('timesheet.created');
        self::assertCount(2, $events);

        $urls = array_map(fn ($e) => $e->getConfiguration()->getUrl(), $events);
        self::assertEquals(['https://a.example.com', 'https://b.example.com'], $urls);
    }

    public function testFindEventsByNameTrimsToMaxEndpointsAndLogsWarning(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
            public array $records = [];
            public function log($level, $message, array $context = []): void
            {
                $this->records[] = ['level' => (string) $level, 'message' => (string) $message, 'context' => $context];
            }
        };

        $endpoints = [];
        for ($i = 0; $i < 15; $i++) {
            $endpoints[] = ['url' => "https://e{$i}.example.com", 'secret' => 's', 'events' => ['timesheet']];
        }

        $service = $this->createService(
            $this->settings($endpoints, max: 3),
            logger: $logger,
        );

        $events = $service->findEventsByName('timesheet.created');
        self::assertCount(3, $events);

        // Ordering must be deterministic (first N kept) so operators have a
        // predictable view of which endpoints actually fire.
        $urls = array_map(fn ($e) => $e->getConfiguration()->getUrl(), $events);
        self::assertSame(
            ['https://e0.example.com', 'https://e1.example.com', 'https://e2.example.com'],
            $urls,
        );

        $truncationMessages = array_filter(
            $logger->records,
            fn (array $r) => str_contains($r['message'], 'exceeds max') && $r['level'] === 'warning',
        );
        self::assertNotEmpty($truncationMessages, 'Expected a "exceeds max" truncation warning to be logged');
    }

    public function testFindEventsByNameSkipsMalformedEntries(): void
    {
        $service = $this->createService([
            'webhook.endpoints' => json_encode([
                ['url' => '', 'events' => ['timesheet']], // empty url
                ['url' => 'https://good.example.com', 'secret' => 's', 'events' => ['timesheet']],
                'not-an-object',
                ['url' => 'https://noarr.example.com', 'events' => 'timesheet'], // events not an array
            ], \JSON_THROW_ON_ERROR),
            'webhook.max_endpoints' => 10,
        ]);

        $events = $service->findEventsByName('timesheet.created');
        self::assertCount(1, $events);
        self::assertEquals('https://good.example.com', $events[0]->getConfiguration()->getUrl());
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
                ['url' => 'https://a.example.com', 'secret' => 'a', 'events' => ['timesheet']],
                ['url' => 'https://b.example.com', 'secret' => 'b', 'events' => ['timesheet', 'invoice']],
            ]),
            $bus,
            $serializer,
        );
        $service->trigger('timesheet.created', new \stdClass());
    }

    public function testTriggerPostsDirectlyForUnsignedEndpoint(): void
    {
        $calls = 0;
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$calls) {
            ++$calls;
            self::assertSame('POST', $method);
            self::assertSame('https://unsigned.example.com', $url);
            $headers = $options['headers'] ?? [];
            self::assertContains('User-Agent: Kimai-Webhook', $headers);
            // Name is passed as X-Webhook-Event
            $hasEventHeader = false;
            foreach ($headers as $h) {
                if (str_starts_with($h, 'X-Webhook-Event: timesheet.created')) {
                    $hasEventHeader = true;
                    break;
                }
            }
            self::assertTrue($hasEventHeader, 'X-Webhook-Event header missing');

            return new MockResponse('', ['http_code' => 200]);
        });

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('toArray')->willReturn(['id' => 1]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        $service = $this->createService(
            $this->settings([
                ['url' => 'https://unsigned.example.com', 'secret' => '', 'events' => ['timesheet']],
            ]),
            $bus,
            $serializer,
            $http,
        );
        $service->trigger('timesheet.created', new \stdClass());

        self::assertSame(1, $calls);
    }

    public function testTriggerMixesSignedAndUnsigned(): void
    {
        $http = new MockHttpClient(new MockResponse('', ['http_code' => 200]));

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $service = $this->createService(
            $this->settings([
                ['url' => 'https://signed.example.com', 'secret' => 'shhh', 'events' => ['timesheet']],
                ['url' => 'https://unsigned.example.com', 'secret' => '', 'events' => ['timesheet']],
            ]),
            $bus,
            null,
            $http,
        );
        $service->trigger('timesheet.created', new \stdClass());
    }

    public function testUnsignedFailureIsLoggedNotThrown(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<array{level: string, message: string}> */
            public array $records = [];
            public function log($level, $message, array $context = []): void
            {
                $this->records[] = ['level' => (string) $level, 'message' => (string) $message];
            }
        };

        $http = new MockHttpClient(new MockResponse('boom', ['http_code' => 500]));

        $service = $this->createService(
            $this->settings([
                ['url' => 'https://dead.example.com', 'secret' => '', 'events' => ['timesheet']],
            ]),
            null,
            null,
            $http,
            $logger,
        );
        $service->trigger('timesheet.created', new \stdClass());

        $warnings = array_filter($logger->records, fn ($r) => $r['level'] === 'warning');
        self::assertNotEmpty($warnings, 'Expected a warning on 500 response from unsigned endpoint');
    }
}
