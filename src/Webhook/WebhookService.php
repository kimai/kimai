<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Webhook;

use App\Configuration\SystemConfiguration;
use App\Entity\WebhookConfiguration;
use App\Entity\WebhookEvent;
use App\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Messenger\SendWebhookMessage;
use Symfony\Component\Webhook\Subscriber;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WebhookService
{
    public const DEFAULT_MAX_ENDPOINTS = 10;

    /**
     * Per-request timeout for unsigned webhook POSTs. Keep this short so a
     * hung receiver doesn't stall the application request that fired the
     * event (messenger is sync by default in Kimai).
     */
    public const UNSIGNED_REQUEST_TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly SystemConfiguration $systemConfiguration,
        private readonly SerializerInterface $serializer,
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger,
        private readonly HttpClientInterface $httpClient
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->parseEndpoints() !== [];
    }

    public function trigger(string $name, mixed $payload): void
    {
        $events = $this->findEventsByName($name);
        if ($events === []) {
            return;
        }

        $content = $this->serializer->toArray($payload, ['groups' => ['Default', 'Entity', 'Expanded']]);
        $eventId = $this->generateEventId($name);

        foreach ($events as $event) {
            $configuration = $event->getConfiguration();
            if ($configuration->getSecret() === '') {
                $this->dispatchUnsigned($name, $eventId, $configuration->getUrl(), $content);
                continue;
            }

            $subscriber = new Subscriber($configuration->getUrl(), $configuration->getSecret());
            $remoteEvent = new RemoteEvent($name, $eventId, $content);
            $this->bus->dispatch(new SendWebhookMessage($subscriber, $remoteEvent));
        }
    }

    /**
     * @return array<WebhookEvent>
     */
    public function findEventsByName(string $name): array
    {
        $parts = explode('.', $name);
        $entityType = $parts[0] ?? '';
        if ($entityType === '') {
            return [];
        }

        $result = [];
        foreach ($this->parseEndpoints() as $index => $endpoint) {
            if (!$this->endpointMatches($endpoint['events'], $name, $entityType)) {
                continue;
            }
            $config = new WebhookConfiguration(
                'kimai_webhook_' . $index,
                $endpoint['url'],
                'json',
                $endpoint['secret'],
                $endpoint['secret'] === '' ? 'none' : 'bearer'
            );
            $result[] = new WebhookEvent($name, $config);
        }

        return $result;
    }

    /**
     * An endpoint fires for an event when the stored `events` array contains
     * EITHER the full event name (`timesheet.created`) OR the entity-level
     * shorthand (`timesheet`, meaning "all actions for this entity").
     *
     * The shorthand exists because:
     *  - pr-5840 stored subscriptions as entity-level booleans, and the
     *    migration preserves that intent by writing `["timesheet"]` instead
     *    of enumerating every action;
     *  - CLI / yaml administrators can subscribe to "everything for this
     *    entity" without having to list every action explicitly.
     *
     * @param array<int, string> $subscribed
     */
    private function endpointMatches(array $subscribed, string $fullEventName, string $entityType): bool
    {
        return \in_array($fullEventName, $subscribed, true)
            || \in_array($entityType, $subscribed, true);
    }

    /**
     * Unsigned dispatch path for endpoints that are saved without a secret.
     *
     * Not every webhook receiver needs (or wants) a shared secret — e.g. an
     * internal consumer on a trusted network, or a receiver that proves origin
     * by IP allowlist. Symfony\Component\Webhook\Subscriber rejects an empty
     * secret at construction, so for these endpoints we bypass the messenger
     * pipeline entirely and POST via HttpClient directly. This keeps the signed
     * path unchanged (still goes through Symfony's webhook transport, preserves
     * whatever async/failure behavior messenger is configured for).
     *
     * The HttpClient is wrapped with NoPrivateNetworkHttpClient at dispatch
     * time (unless `kimai.webhook.allow_private_network` is set) as a second
     * line of defense against DNS-rebinding past the form-level URL validator.
     *
     * Failures are logged, never thrown — a single bad endpoint can't abort the
     * fan-out to the rest.
     *
     * @param array<mixed> $content
     */
    private function dispatchUnsigned(string $name, string $eventId, string $url, array $content): void
    {
        try {
            $response = $this->buildUnsignedHttpClient()->request('POST', $url, [
                'json' => $content,
                'headers' => [
                    'X-Webhook-Event' => $name,
                    'X-Webhook-Id' => $eventId,
                    'User-Agent' => 'Kimai-Webhook',
                ],
                'timeout' => self::UNSIGNED_REQUEST_TIMEOUT_SECONDS,
            ]);
            $status = $response->getStatusCode();
            if ($status >= 400) {
                $this->logger->warning(
                    'Unsigned webhook POST returned {status} for {url}',
                    ['status' => $status, 'url' => $url],
                );
            }
        } catch (TransportExceptionInterface $e) {
            $this->logger->error(
                'Unsigned webhook POST failed for {url}: {message}',
                ['url' => $url, 'message' => $e->getMessage()],
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                'Unexpected error dispatching unsigned webhook for {url}: {message}',
                ['url' => $url, 'message' => $e->getMessage()],
            );
        }
    }

    /**
     * Unless SSRF protection is explicitly disabled, wrap the injected client
     * with NoPrivateNetworkHttpClient. That decorator re-resolves DNS at send
     * time and blocks private/loopback/link-local/reserved IPs, catching
     * DNS-rebinding attacks that slip past the form-level validator.
     */
    private function buildUnsignedHttpClient(): HttpClientInterface
    {
        if ($this->systemConfiguration->find('webhook.allow_private_network')) {
            return $this->httpClient;
        }

        return new NoPrivateNetworkHttpClient($this->httpClient);
    }

    /**
     * Reads `webhook.endpoints` and returns normalized, validated entries.
     *
     * Malformed JSON or entries with an empty URL / non-array events list are
     * dropped, with a warning logged for each drop so admins can debug why
     * events aren't firing. Endpoints with empty secrets are *kept* — they are
     * dispatched via the unsigned path. If the decoded list exceeds
     * `webhook.max_endpoints` a warning is also logged; the form-level
     * validator rejects overflow before this path is hit, but we guard here
     * too in case the value was written via CLI/API/yaml bypass.
     *
     * @return array<int, array{url: string, secret: string, events: array<int, string>}>
     */
    private function parseEndpoints(): array
    {
        $raw = $this->systemConfiguration->find('webhook.endpoints');
        if (!\is_string($raw) || trim($raw) === '' || trim($raw) === '[]') {
            return [];
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($raw, true, 16, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->warning('Failed to parse webhook.endpoints JSON: {message}', ['message' => $e->getMessage()]);

            return [];
        }

        if (!\is_array($decoded)) {
            $this->logger->warning('webhook.endpoints is not a JSON array; ignoring.');

            return [];
        }

        $maxRaw = $this->systemConfiguration->find('webhook.max_endpoints');
        $max = \is_int($maxRaw) ? $maxRaw : self::DEFAULT_MAX_ENDPOINTS;

        $valid = [];
        $droppedMalformed = 0;
        foreach ($decoded as $entry) {
            if (!\is_array($entry)) {
                ++$droppedMalformed;
                continue;
            }
            $url = $entry['url'] ?? null;
            $events = $entry['events'] ?? null;
            if (!\is_string($url) || $url === '' || !\is_array($events)) {
                ++$droppedMalformed;
                continue;
            }
            $valid[] = [
                'url' => $url,
                'secret' => \is_string($entry['secret'] ?? null) ? $entry['secret'] : '',
                'events' => array_values(array_filter($events, 'is_string')),
            ];
        }

        if ($droppedMalformed > 0) {
            $this->logger->warning(
                'Dropped {count} malformed webhook endpoint entries from configuration',
                ['count' => $droppedMalformed],
            );
        }

        if (\count($valid) > $max) {
            $this->logger->warning(
                'Webhook endpoint count {count} exceeds max {max}; truncating. Raise `kimai.webhook.max_endpoints` to lift the cap.',
                ['count' => \count($valid), 'max' => $max],
            );
            $valid = \array_slice($valid, 0, $max);
        }

        return $valid;
    }

    private function generateEventId(string $name): string
    {
        return $name . '_' . bin2hex(random_bytes(8));
    }
}
