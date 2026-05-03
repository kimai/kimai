<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Webhook;

use App\Configuration\SystemConfiguration;
use App\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Messenger\SendWebhookMessage;
use Symfony\Component\Webhook\Subscriber;
use Symfony\Contracts\Service\ResetInterface;

final class WebhookService implements ResetInterface
{
    /**
     * @var array<int, array{url: string, secret: string, events: array<int, string>}>|null
     */
    private ?array $cachedEndpoints = null;

    public function __construct(
        private readonly SystemConfiguration $systemConfiguration,
        private readonly SerializerInterface $serializer,
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function reset(): void
    {
        $this->cachedEndpoints = null;
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
            $payload = [
                'id' => $eventId,
                'event' => $event->getName(),
                'data' => $content,
            ];

            $subscriber = new Subscriber($event->getUrl(), $event->getSecret());
            $remoteEvent = new RemoteEvent($name, $eventId, $payload);
            $this->bus->dispatch(new SendWebhookMessage($subscriber, $remoteEvent));
        }
    }

    /**
     * @return array<int, WebhookEvent>
     */
    private function findEventsByName(string $name): array
    {
        $result = [];
        foreach ($this->parseEndpoints() as $endpoint) {
            if (!\in_array($name, $endpoint['events'], true)) {
                continue;
            }
            $result[] = new WebhookEvent(
                $name,
                $endpoint['url'],
                $endpoint['secret']
            );
        }

        return $result;
    }

    /**
     * Reads `webhook.endpoints` and returns normalized, validated entries.
     *
     * Malformed JSON or entries with an empty URL / non-array events list are
     * dropped, with a warning logged for each drop.
     *
     * @return array<int, array{url: string, secret: string, events: array<int, string>}>
     */
    private function parseEndpoints(): array
    {
        if ($this->cachedEndpoints === null) {
            $raw = $this->systemConfiguration->find('webhook.endpoints');
            if (!\is_string($raw) || trim($raw) === '' || trim($raw) === '[]') {
                return $this->cachedEndpoints = [];
            }

            try {
                $decoded = json_decode($raw, true, 16, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->logger->warning('Failed to parse webhook.endpoints JSON: {message}', ['message' => $e->getMessage()]);

                return $this->cachedEndpoints = [];
            }

            if (!\is_array($decoded)) {
                $this->logger->warning('webhook.endpoints is not a JSON array; ignoring.');

                return $this->cachedEndpoints = [];
            }

            $max = $this->systemConfiguration->getMaxWebhookEndpoints();

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
                    'Webhook endpoint count {count} exceeds max {max}; Increase `webhook.max_endpoints` to raise the limit.',
                    ['count' => \count($valid), 'max' => $max],
                );
                $valid = \array_slice($valid, 0, $max);
            }

            $this->cachedEndpoints = $valid;
        }

        return $this->cachedEndpoints;
    }

    private function generateEventId(string $name): string
    {
        return $name . '_' . bin2hex(random_bytes(8));
    }
}
