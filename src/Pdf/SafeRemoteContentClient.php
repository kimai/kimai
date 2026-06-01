<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Pdf;

use Mpdf\Http\ClientInterface;
use Mpdf\PsrHttpMessageShim\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Bridges mPDF's HTTP client to Symfonys NoPrivateNetworkHttpClient.
 *
 * Prevents the PDF renderer from issuing outbound requests to private network targets,
 * closing the SSRF vector for `<img src="...">` references in custom Twig invoice templates.
 *
 * Blocked requests are translated to a non-2xx response so mPDF logs the failure
 * and renders a placeholder for the missing image without aborting PDF generation.
 *
 * @see https://github.com/kimai/kimai/security/advisories/GHSA-pj8j-p4g4-4vw8
 */
final class SafeRemoteContentClient implements ClientInterface
{
    /**
     * Timeout in seconds: a slow or unreachable remote target should not block PDF rendering.
     */
    private const TIMEOUT = 10;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger
    )
    {
    }

    public function sendRequest(RequestInterface $request): Response
    {
        $url = (string) $request->getUri();
        try {
            $response = $this->client->request(
                $request->getMethod(),
                $url,
                [
                    'headers' => $this->flattenHeaders($request),
                    'timeout' => self::TIMEOUT,
                    'max_duration' => self::TIMEOUT,
                ]
            );

            return new Response(
                $response->getStatusCode(),
                [],
                $response->getContent(false)
            );
        } catch (HttpClientExceptionInterface $e) {
            // Request blocked (private network), DNS failure, timeout, etc.
            $this->logger->error(\sprintf('Failed fetching from URL "%s" with : %s', $url, $e->getMessage()));

            return new Response(502);
        }
    }

    /**
     * @return array<string, string>
     */
    private function flattenHeaders(RequestInterface $request): array
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        return $headers;
    }
}
