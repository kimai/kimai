<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Webhook;

use App\Configuration\SystemConfiguration;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Server\RequestConfiguratorInterface;
use Symfony\Component\Webhook\Server\TransportInterface;
use Symfony\Component\Webhook\Subscriber;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WebhookTransport implements TransportInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly RequestConfiguratorInterface $headers,
        private readonly RequestConfiguratorInterface $body,
        private readonly RequestConfiguratorInterface $signer,
        private readonly SystemConfiguration $configuration,
    ) {
    }

    public function send(Subscriber $subscriber, RemoteEvent $event): void
    {
        $options = new HttpOptions();

        $this->headers->configure($event, $subscriber->getSecret(), $options);
        $this->body->configure($event, $subscriber->getSecret(), $options);
        $this->signer->configure($event, $subscriber->getSecret(), $options);

        $client = $this->client;
        if (!$this->configuration->isWebhookPrivateNetworkAllowed()) {
            $client = new NoPrivateNetworkHttpClient($client);
        }

        // some http clients are lazy and only execute if we ask for content or status code
        $response = $client->request('POST', $subscriber->getUrl(), $options->toArray());

        // we fetch the response to make sure that a lazy loading client really dispatched the request
        $response->getStatusCode();
    }
}
