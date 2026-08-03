<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\DataFixtures\UserFixtures;
use App\Entity\AccessToken;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * @group integration
 */
class TokenScopeControllerTest extends APIControllerBaseTestCase
{
    public function testTokenEndpointForLegacyTokenReturnsEverythingTrue(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/token');

        $result = $this->decodeMatrix($client);
        // the full catalog is returned
        self::assertArrayHasKey('timesheet', $result);
        self::assertArrayHasKey('customer', $result);
        self::assertArrayHasKey('read', $result['timesheet']);
        // a legacy fixture token (scopes = null) is allowed to do everything
        self::assertTrue($result['timesheet']['read']);
        self::assertTrue($result['timesheet']['create']);
        self::assertTrue($result['customer']['delete']);
    }

    /**
     * @return array<string, array<string, bool>>
     */
    private function decodeMatrix(HttpKernelBrowser $client): array
    {
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);
        self::assertIsArray($result);

        $matrix = [];
        foreach ($result as $resource => $actions) {
            self::assertIsString($resource);
            self::assertIsArray($actions);
            foreach ($actions as $action => $granted) {
                self::assertIsString($action);
                self::assertIsBool($granted);
                $matrix[$resource][$action] = $granted;
            }
        }

        return $matrix;
    }

    private function createScopedClient(string $rawToken, array $scopes): KernelBrowser
    {
        $client = self::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer ' . $rawToken]);

        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->findOneBy(['username' => UserFixtures::USERNAME_USER]);
        self::assertInstanceOf(User::class, $user);

        $token = new AccessToken($user, $rawToken);
        $token->setName('scoped-test');
        $token->setScopes($scopes);
        $em->persist($token);
        $em->flush();

        return $client;
    }

    /**
     * @param array<string> $body
     */
    private function jsonBody(array $body): string
    {
        return (string) json_encode($body);
    }

    public function testScopedTokenMatrixReflectsGrantedScopes(): void
    {
        $client = $this->createScopedClient('scoped_token_matrix_1', ['timesheet:read']);

        $client->request('GET', '/api/token');
        self::assertTrue($client->getResponse()->isSuccessful());

        $result = $this->decodeMatrix($client);
        // granted scope
        self::assertTrue($result['timesheet']['read']);
        // not granted to the token -> false, even though the user could do it
        self::assertFalse($result['timesheet']['create']);
        self::assertFalse($result['customer']['read']);
    }

    public function testScopedTokenAllowsGrantedScope(): void
    {
        $client = $this->createScopedClient('scoped_token_allow_1', ['timesheet:read']);

        $client->request('GET', '/api/timesheets');
        self::assertTrue($client->getResponse()->isSuccessful());
    }

    public function testScopedTokenDeniesMissingScope(): void
    {
        // the user (ROLE_USER) is allowed to create tags, but the token is not scoped for it
        $client = $this->createScopedClient('scoped_token_deny_1', ['timesheet:read']);

        $client->request('POST', '/api/tags', [], [], ['CONTENT_TYPE' => 'application/json'], $this->jsonBody(['name' => 'scope-denied']));
        self::assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testEmptyScopeSetDeniesScopedEndpointsButAllowsMeta(): void
    {
        // an explicit empty scope set must grant nothing (not full access)
        $client = $this->createScopedClient('scoped_token_empty_1', []);

        $client->request('GET', '/api/timesheets');
        self::assertEquals(403, $client->getResponse()->getStatusCode());

        // non-scoped meta endpoints stay reachable
        $client->request('GET', '/api/token');
        self::assertTrue($client->getResponse()->isSuccessful());
    }

    public function testIntrospectionEndpointReachableWithMinimalScope(): void
    {
        // even a token scoped to something unrelated can read /api/token (ignored endpoint)
        $client = $this->createScopedClient('scoped_token_intro_1', ['customer:read']);

        $client->request('GET', '/api/token');
        self::assertTrue($client->getResponse()->isSuccessful());
    }
}
