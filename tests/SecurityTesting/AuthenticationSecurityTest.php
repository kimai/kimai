<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\SecurityTesting;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Tests\API\APIControllerBaseTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security tests for OWASP WSTG v4.2 category "4.4 Authentication Testing" (WSTG-ATHN).
 *
 * Verifies that the token based API authentication cannot be bypassed with
 * missing, guessed or deprovisioned credentials.
 */
#[Group('integration')]
#[Group('security')]
class AuthenticationSecurityTest extends APIControllerBaseTestCase
{
    /**
     * Seeded by src/Command/ResetTestCommand.php as a bare literal - see
     * testDisabledFixtureAccountExistsAndIsDisabled() for why this is pinned.
     */
    private const DISABLED_FIXTURE_USERNAME = 'chris_user';

    /**
     * WSTG-ATHN-01 / WSTG-ATHN-02:
     * Every secured API endpoint must reject requests without credentials with HTTP 401.
     */
    public function testApiRejectsRequestsWithoutCredentials(): void
    {
        $client = self::createClient();

        foreach (['/api/ping', '/api/timesheets', '/api/customers', '/api/users', '/api/activities', '/api/projects'] as $url) {
            $this->assertRequestIsSecured($client, $url);
        }
    }

    /**
     * WSTG-ATHN-03:
     * A wrong (guessed or brute-forced) bearer token must be rejected without
     * leaking information about valid usernames or tokens.
     */
    public function testApiRejectsInvalidBearerToken(): void
    {
        $client = self::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer invalid-guessed-token']);
        $this->request($client, '/api/ping');
        $response = $client->getResponse();

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $content = $response->getContent();
        self::assertIsString($content);
        // the response must not reveal why the authentication failed or who might be a valid user
        foreach (['Invalid', 'User', 'token', 'Exception', 'Trace'] as $leak) {
            self::assertStringNotContainsStringIgnoringCase($leak, $content, \sprintf('Failed authentication leaks "%s"', $leak));
        }
    }

    /**
     * WSTG-ATHN-06:
     * A disabled account must not be able to authenticate, even when presenting
     * its previously valid access token (fixture user "chris_user" is disabled).
     */
    public function testDisabledAccountCannotAuthenticateWithValidToken(): void
    {
        $client = self::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer ' . UserFixtures::DEFAULT_API_TOKEN . '_inactive']);
        $this->request($client, '/api/ping');

        self::assertSame(
            Response::HTTP_UNAUTHORIZED,
            $client->getResponse()->getStatusCode(),
            'Disabled account could authenticate with a valid access token'
        );
    }

    /**
     * WSTG-ATHN-06:
     * Tokens of a disabled account must not work for any other endpoint either.
     */
    public function testDisabledAccountCannotAccessSecuredEndpoints(): void
    {
        $client = self::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer ' . UserFixtures::DEFAULT_API_TOKEN . '_inactive']);

        foreach (['/api/timesheets', '/api/customers', '/api/users'] as $url) {
            $this->request($client, $url);
            self::assertSame(
                Response::HTTP_UNAUTHORIZED,
                $client->getResponse()->getStatusCode(),
                \sprintf('Disabled account could access %s', $url)
            );
        }
    }

    /**
     * Fixture guard for the two WSTG-ATHN-06 tests above.
     *
     * Those tests prove a point only if the "_inactive" token really belongs to
     * an existing, disabled account. If that account were renamed, deleted, or
     * its token suffix changed, the endpoint would answer 401 because the token
     * is unknown - and both tests would keep passing for the wrong reason.
     *
     * The account is seeded by src/Command/ResetTestCommand.php as a bare string
     * literal, with no constant tying it to src/DataFixtures/UserFixtures.php,
     * so nothing else in the codebase catches that drift.
     */
    public function testDisabledFixtureAccountExistsAndIsDisabled(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        // visible=2 selects only the disabled (invisible) users
        $this->request($client, '/api/users', 'GET', ['visible' => 2]);
        self::assertTrue($client->getResponse()->isSuccessful(), 'Could not list disabled users');

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $users = json_decode($content, true);
        self::assertIsArray($users);

        $found = false;
        foreach ($users as $user) {
            if (\is_array($user) && ($user['username'] ?? null) === self::DISABLED_FIXTURE_USERNAME) {
                $found = true;
                break;
            }
        }

        self::assertTrue(
            $found,
            \sprintf(
                'Fixture account "%s" is missing from the disabled users - the WSTG-ATHN-06 tests above would pass vacuously. Check src/Command/ResetTestCommand.php.',
                self::DISABLED_FIXTURE_USERNAME
            )
        );
    }
}
