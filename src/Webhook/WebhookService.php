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
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Messenger\SendWebhookMessage;
use Symfony\Component\Webhook\Subscriber;

final class WebhookService
{
    public function __construct(
        private readonly SystemConfiguration $systemConfiguration,
        private readonly SerializerInterface $serializer,
        private readonly MessageBusInterface $bus
    ) {
    }

    public function isConfigured(): bool
    {
        $url = $this->systemConfiguration->find('webhook.endpoint_url');

        return \is_string($url) && $url !== '';
    }

    public function trigger(string $name, mixed $payload): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        $content = $this->serializer->toArray($payload, ['groups' => ['Default', 'Entity', 'Expanded']]);

        foreach ($this->findEventsByName($name) as $event) {
            $configuration = $event->getConfiguration();
            $subscriber = new Subscriber($configuration->getUrl(), $configuration->getSecret());
            $remoteEvent = new RemoteEvent($name, $name . '_' . uniqid(microtime()), $content);
            $message = new SendWebhookMessage($subscriber, $remoteEvent);
            $this->bus->dispatch($message);
        }
    }

    /**
     * @return array<WebhookEvent>
     */
    public function findEventsByName(string $name): array
    {
        $url = $this->systemConfiguration->find('webhook.endpoint_url');
        $secret = $this->systemConfiguration->find('webhook.secret_token');

        if (!\is_string($url) || $url === '') {
            return [];
        }

        $configuration = new WebhookConfiguration('kimai_webhook', $url, 'json', \is_string($secret) ? $secret : '', 'bearer');

        return [
            new WebhookEvent($name, $configuration),
        ];
    }
}
