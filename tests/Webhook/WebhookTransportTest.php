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
use App\Webhook\WebhookTransport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Server\RequestConfiguratorInterface;
use Symfony\Component\Webhook\Subscriber;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[CoversClass(WebhookTransport::class)]
class WebhookTransportTest extends TestCase
{
    /**
     * @param array<string, mixed> $settings
     */
    private function createConfiguration(array $settings = []): SystemConfiguration
    {
        $configLoader = $this->createMock(ConfigLoaderInterface::class);
        $configLoader->method('getConfigurations')->willReturn([]);

        return new SystemConfiguration($configLoader, $settings);
    }

    /**
     * @param callable(RemoteEvent, string, HttpOptions): void|null $callback
     */
    private function configurator(?callable $callback = null): RequestConfiguratorInterface
    {
        $mock = $this->createMock(RequestConfiguratorInterface::class);
        $expectation = $mock->expects(self::once())->method('configure');
        if ($callback !== null) {
            $expectation->willReturnCallback($callback);
        }

        return $mock;
    }

    private function createTransport(
        HttpClientInterface $client,
        RequestConfiguratorInterface $headers,
        RequestConfiguratorInterface $body,
        RequestConfiguratorInterface $signer,
        bool $allowPrivateNetwork = true,
    ): WebhookTransport {
        return new WebhookTransport(
            $client,
            $headers,
            $body,
            $signer,
            $this->createConfiguration(['webhook.allow_private_network' => $allowPrivateNetwork]),
        );
    }

    public function testSendInvokesAllConfiguratorsAndPostsToSubscriberUrl(): void
    {
        $event = new RemoteEvent('timesheet.created', 'id-1', ['foo' => 'bar']);
        $subscriber = new Subscriber('https://example.com/webhook', 'shhh');

        $calls = [];
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$calls): MockResponse {
            $calls[] = ['method' => $method, 'url' => $url, 'options' => $options];

            return new MockResponse('', ['http_code' => 200]);
        });

        $headersAssert = function (RemoteEvent $e, string $secret, HttpOptions $options) use ($event): void {
            self::assertSame($event, $e);
            self::assertSame('shhh', $secret);
            $options->setHeaders(['X-Test' => '1']);
        };

        $bodyAssert = function (RemoteEvent $e, string $secret, HttpOptions $options) use ($event): void {
            self::assertSame($event, $e);
            self::assertSame('shhh', $secret);
            $options->setBody('{"foo":"bar"}');
        };

        $signerAssert = function (RemoteEvent $e, string $secret, HttpOptions $options) use ($event): void {
            self::assertSame($event, $e);
            self::assertSame('shhh', $secret);
        };

        $sut = $this->createTransport(
            $http,
            $this->configurator($headersAssert),
            $this->configurator($bodyAssert),
            $this->configurator($signerAssert),
        );

        $sut->send($subscriber, $event);

        self::assertCount(1, $calls);
        self::assertSame('POST', $calls[0]['method']);
        self::assertSame('https://example.com/webhook', $calls[0]['url']);
        self::assertSame('{"foo":"bar"}', $calls[0]['options']['body']);
        self::assertContains('X-Test: 1', $calls[0]['options']['headers']);
    }

    public function testSendDoesNotWrapClientWhenPrivateNetworkAllowed(): void
    {
        $http = new MockHttpClient(new MockResponse('', ['http_code' => 200]));

        $sut = $this->createTransport(
            $http,
            $this->configurator(),
            $this->configurator(),
            $this->configurator(),
            allowPrivateNetwork: true,
        );

        // 127.0.0.1 would be blocked by NoPrivateNetworkHttpClient. The fact that
        // this completes without exception proves the wrapper was NOT applied.
        $sut->send(new Subscriber('http://127.0.0.1/hook', 's'), new RemoteEvent('x', 'id', []));

        self::assertSame(1, $http->getRequestsCount());
    }

    public function testSendWrapsClientWhenPrivateNetworkDisallowed(): void
    {
        $http = new MockHttpClient(new MockResponse('', ['http_code' => 200]));

        $sut = $this->createTransport(
            $http,
            $this->configurator(),
            $this->configurator(),
            $this->configurator(),
            allowPrivateNetwork: false,
        );

        $this->expectException(TransportException::class);
        $sut->send(new Subscriber('http://127.0.0.1/hook', 's'), new RemoteEvent('x', 'id', []));
    }
}
