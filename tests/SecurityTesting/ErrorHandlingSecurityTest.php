<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\SecurityTesting;

use App\Entity\User;
use App\Tests\API\APIControllerBaseTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security tests for OWASP WSTG v4.2 categories "4.8 Testing for Error Handling" (WSTG-ERRH)
 * and "4.2 Configuration and Deployment Management Testing" (WSTG-CONF).
 *
 * Verifies that error responses do not disclose stack traces, class names or file
 * paths and that debug tooling is not exposed.
 */
#[Group('integration')]
#[Group('security')]
class ErrorHandlingSecurityTest extends APIControllerBaseTestCase
{
    /**
     * WSTG-ERRH-01 / WSTG-CONF-05:
     * Unknown API routes must return a minimal JSON error without internals.
     */
    public function testUnknownApiRouteDoesNotLeakInternals(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/api/this-endpoint-does-not-exist');
        $response = $client->getResponse();

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $body = $response->getContent();
        self::assertIsString($body);
        self::assertSame(
            ['code' => Response::HTTP_NOT_FOUND, 'message' => 'Not Found'],
            json_decode($body, true)
        );

        $this->assertNoInternalInformationDisclosed($body);
    }

    /**
     * WSTG-ERRH-02:
     * Authentication errors must be generic and identical for all failure reasons,
     * so an attacker cannot enumerate valid usernames or tokens.
     */
    public function testUnauthorizedResponseIsMinimalAndGeneric(): void
    {
        $client = self::createClient();
        $this->request($client, '/api/ping');
        $response = $client->getResponse();

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $body = $response->getContent();
        self::assertIsString($body);
        self::assertEquals(
            ['message' => 'Unauthorized', 'code' => Response::HTTP_UNAUTHORIZED],
            json_decode($body, true)
        );

        $this->assertNoInternalInformationDisclosed($body);
    }

    /**
     * WSTG-CONF-05:
     * Debug and profiler tooling must not be reachable, and must 404 rather than
     * error out.
     *
     * Scope: under APP_ENV=test these routes are not registered at all (see
     * Kernel::configureRoutes), so this can only prove the runtime behaviour -
     * it cannot prove the profiler is off in production. That guarantee is
     * asserted from configuration in
     * SecurityConfigurationTest::testProfilerIsConfinedToDevelopmentEnvironments.
     *
     * The original assertion was only "not 200", which also accepts a 500 - the
     * very stack-trace leak this WSTG category is about. A server error is now
     * a failure too.
     */
    public function testDebugAndProfilerRoutesAreNotExposed(): void
    {
        $client = self::createClient();

        foreach (['/_profiler', '/_wdt/123456', '/app_dev.php'] as $url) {
            $client->request('GET', $url);
            $response = $client->getResponse();
            $status = $response->getStatusCode();

            self::assertNotSame(
                Response::HTTP_OK,
                $status,
                \sprintf('Debug URL %s is accessible', $url)
            );
            self::assertLessThan(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $status,
                \sprintf('Debug URL %s returned a server error (%d) instead of being absent', $url, $status)
            );

            $body = $response->getContent();
            self::assertIsString($body);
            self::assertStringNotContainsString('Symfony Profiler', $body, \sprintf('Debug URL %s exposes the profiler', $url));
        }
    }

    /**
     * WSTG-CONF (information leakage through headers):
     * Search engine indexing is disabled application wide via the X-Robots-Tag header.
     */
    public function testSearchEngineIndexingIsDisabled(): void
    {
        $client = self::createClient();
        $client->request('GET', '/en/login');

        $header = $client->getResponse()->headers->get('X-Robots-Tag');
        self::assertIsString($header, 'Missing X-Robots-Tag header');
        self::assertStringContainsString('noindex', $header);
    }

    private function assertNoInternalInformationDisclosed(string $body): void
    {
        foreach (['Exception', 'Stack trace', 'Trace', '.php', '/var/', 'Symfony\\', 'vendor/'] as $leak) {
            self::assertStringNotContainsString($leak, $body, \sprintf('Error response leaks internal information: %s', $leak));
        }
    }
}
