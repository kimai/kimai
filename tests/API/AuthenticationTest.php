<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

/**
 * Make sure that the deprecated API login with X-AUTH-USER and X-AUTH-TOKEN is rejected.
 */
#[Group('legacy')]
#[Group('integration')]
class AuthenticationTest extends APIControllerBaseTestCase
{
    public function testPingWithAccessToken(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/ping');
        $response = $client->getResponse()->getContent();
        self::assertIsString($response);
        $result = json_decode($response, true);

        self::assertIsArray($result);
        self::assertEquals(['message' => 'pong'], $result);
    }

    public function testPingWithDeprecatedApiPasswordIsRejected(): void
    {
        // valid credentials of the deprecated API password authentication
        $client = self::createClient([], [
            'HTTP_X_AUTH_USER' => UserFixtures::USERNAME_USER,
            'HTTP_X_AUTH_TOKEN' => UserFixtures::DEFAULT_API_TOKEN,
        ]);

        $this->request($client, '/api/ping');

        $this->assertApiException($client->getResponse(), [
            'code' => Response::HTTP_UNAUTHORIZED,
            'message' => 'Unauthorized',
        ]);
    }
}
