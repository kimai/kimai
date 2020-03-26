<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group integration
 */
trait RateControllerTestTrait
{
    abstract protected function getRateUrl(string $id = '1'): string;

    public function testAddRateMissingEntityAction()
    {
        $data = [
            'user' => 1,
            'rate' => 12.34,
            'internal_rate' => 6.66,
            'is_fixed' => false
        ];
        $this->assertEntityNotFoundForPost(User::ROLE_ADMIN, $this->getRateUrl(99), $data, 'Not found');
    }

    public function testAddRateMissingUserAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'user' => 33,
            'rate' => 12.34,
            'internal_rate' => 6.66,
            'is_fixed' => false
        ];
        $this->request($client, $this->getRateUrl(), 'POST', [], json_encode($data));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['user']);
    }

    public function testAddRateActionWithInvalidUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $data = [
            'user' => null,
            'rate' => 12.34,
            'internal_rate' => 6.66,
            'is_fixed' => false
        ];
        $this->request($client, $this->getRateUrl(), 'POST', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized', $json['message']);
    }

    public function testAddRateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'user' => null,
            'rate' => 12.34,
            'internal_rate' => 6.66,
            'is_fixed' => false
        ];
        $this->request($client, $this->getRateUrl(), 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertRateStructure($result, null);
        $this->assertNotEmpty($result['id']);
        $this->assertEquals(12.34, $result['rate']);
        $this->assertEquals(6.66, $result['internalRate']);
        $this->assertFalse($result['isFixed']);
    }

    public function testAddFixedRateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'user' => 1,
            'rate' => 12.34,
            'internal_rate' => 6.66,
            'is_fixed' => true
        ];
        $this->request($client, $this->getRateUrl(), 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertRateStructure($result, 1);
        $this->assertNotEmpty($result['id']);
        $this->assertEquals(12.34, $result['rate']);
        $this->assertEquals(6.66, $result['internalRate']);
        $this->assertTrue($result['isFixed']);
    }

    // TODO get rates
    // TODO get rates - with invalid user

    // TODO delete rate
    // TODO delete rate - rate not found
    // TODO delete rate - entity not found
    // TODO delete rate - with invalid user

    protected function assertRateStructure(array $result, $user = null)
    {
        $expectedKeys = [
            'id', 'rate', 'internalRate', 'isFixed', 'user'
        ];

        $actual = array_keys($result);
        sort($actual);
        sort($expectedKeys);

        $this->assertEquals($expectedKeys, $actual, 'Rate structure does not match');

        if (null !== $user) {
            self::assertIsArray($result['user'], 'Rate user is not an array');
            self::assertEquals($user, $result['user']['id'], 'Rate user does not match');
        } else {
            self::assertNull($result['user']);
        }
    }
}
