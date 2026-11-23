<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Pdf;

use App\Pdf\SafeRemoteContentClient;
use Mpdf\PsrHttpMessageShim\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(SafeRemoteContentClient::class)]
class SafeRemoteContentClientTest extends TestCase
{
    public function testSuccessfulResponseIsForwarded(): void
    {
        $client = new MockHttpClient(
            new MockResponse('image-bytes', ['http_code' => 200])
        );

        $sut = new SafeRemoteContentClient($client, $this->createStub(LoggerInterface::class));
        $response = $sut->sendRequest(new Request('GET', 'https://example.com/logo.png'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image-bytes', $response->getBody()->getContents());
    }

    public function testNon2xxResponseIsForwardedWithoutThrowing(): void
    {
        $client = new MockHttpClient(
            new MockResponse('not found', ['http_code' => 404])
        );

        $sut = new SafeRemoteContentClient($client, $this->createStub(LoggerInterface::class));
        $response = $sut->sendRequest(new Request('GET', 'https://example.com/missing.png'));

        self::assertSame(404, $response->getStatusCode());
    }

    public function testTransportExceptionResultsInNon2xxResponse(): void
    {
        // Simulates NoPrivateNetworkHttpClient blocking the request, a DNS
        // failure, or a connection timeout — none of which must crash the
        // PDF rendering pipeline.
        $client = new MockHttpClient(static function (): MockResponse {
            throw new TransportException('IP blocked');
        });

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $sut = new SafeRemoteContentClient($client, $logger);
        $response = $sut->sendRequest(new Request('GET', 'http://127.0.0.1/internal'));

        self::assertSame(502, $response->getStatusCode());
    }

    public function testRequestIsBlockedWhenWrappedWithNoPrivateNetworkHttpClient(): void
    {
        // Wraps a mock client that would otherwise succeed. The decorator
        // must reject the localhost URL before any request is dispatched.
        $inner = new MockHttpClient(new MockResponse('should-not-be-reached'));
        $safe = new NoPrivateNetworkHttpClient($inner);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $sut = new SafeRemoteContentClient($safe, $logger);
        $response = $sut->sendRequest(new Request('GET', 'http://127.0.0.1/internal'));

        self::assertSame(502, $response->getStatusCode());
        self::assertSame('', $response->getBody()->getContents());
    }
}
