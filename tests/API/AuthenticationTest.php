<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace API;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Tests\API\APIControllerBaseTest;
use Symfony\Component\HttpFoundation\Response;

/**
 * These tests make sure, that the deprecated API login with X-AUTH-USER and X-AUTH-TOKEN still works.
 *
 * @group legacy
 * @group integration
 */
class AuthenticationTest extends APIControllerBaseTest
{
    public function testPinIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/ping');
    }

    public function testPingWithAccessToken(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/ping');
        $response = $client->getResponse()->getContent();
        $this->assertIsString($response);
        $result = json_decode($response, true);

        $this->assertIsArray($result);
        $this->assertEquals(['message' => 'pong'], $result);
    }

    public function testPingWithAuthTokenAndUsername(): void
    {
        $client = self::createClient([], $this->getAuthHeader(UserFixtures::USERNAME_USER, UserFixtures::DEFAULT_API_TOKEN));

        $this->assertAccessIsGranted($client, '/api/ping');
        $response = $client->getResponse()->getContent();
        $this->assertIsString($response);
        $result = json_decode($response, true);

        $this->assertIsArray($result);
        $this->assertEquals(['message' => 'pong'], $result);
    }

    public function testPingWithInvalidAuthTokenAndUsername(): void
    {
        $client = self::createClient([], $this->getAuthHeader(UserFixtures::USERNAME_USER, 'xxxx'));
        $url = '/api/ping';
        $method = 'GET';

        $this->request($client, $url, $method);
        $response = $client->getResponse();

        $data = [
            'message' => 'Invalid credentials',
        ];

        $this->assertIsString($response->getContent());

        $this->assertEquals(
            $data,
            json_decode($response->getContent(), true),
            \sprintf('The secure URL %s is not protected.', $url)
        );

        $this->assertEquals(
            Response::HTTP_FORBIDDEN,
            $response->getStatusCode(),
            \sprintf('The secure URL %s has the wrong status code %s.', $url, $response->getStatusCode())
        );
    }

    /**
     * @return array<string, string>
     */
    private function getAuthHeader(string $username, string $password): array
    {
        return [
            'HTTP_X_AUTH_USER' => $username,
            'HTTP_X_AUTH_TOKEN' => $password,
        ];
    }
}
