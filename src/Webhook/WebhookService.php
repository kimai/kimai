<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Webhook;

use App\Entity\WebhookConfiguration;
use App\Entity\WebhookEvent;
use App\Serializer\SerializerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Messenger\SendWebhookMessage;
use Symfony\Component\Webhook\Subscriber;

final class WebhookService
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly MessageBusInterface $bus
    )
    {
    }

    public function hasWebhook(string $name): bool
    {
        return $name === 'user.created';
    }

    public function trigger(string $name, mixed $payload): void
    {
        $content = $this->serializer->toArray($payload, ['groups' => ['Default', 'Entity', 'Expanded']]);

        foreach ($this->findEventsByName($name) as $event) {
            $configuration = $event->getConfiguration();
            $subscriber = new Subscriber($configuration->getUrl(), $configuration->getSecret());
            $remoteEvent = new RemoteEvent($name, $name . '_' . uniqid(microtime()), $content);
            $message = new SendWebhookMessage($subscriber, $remoteEvent);
            $this->bus->dispatch($message);
        }
    }

    public function findEventsByName(string $name): array
    {
        $url = 'TODO';
        $secret = 'TODO';

        $configuration = new WebhookConfiguration('n8n', $url, 'json', $secret, 'bearer');

        return [
            new WebhookEvent($name, $configuration),
        ];
    }
}
