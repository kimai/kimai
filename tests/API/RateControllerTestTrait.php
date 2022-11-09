<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\ActivityRate;
use App\Entity\CustomerRate;
use App\Entity\ProjectRate;
use App\Entity\RateInterface;
use App\Entity\User;

/**
 * @group integration
 */
trait RateControllerTestTrait
{
    /**
     * @param string|int $id
     * @param string|int|null $rateId
     * @return string
     */
    abstract protected function getRateUrl($id = '1', $rateId = null): string;

    abstract protected function getRateUrlByRate(RateInterface $rate, bool $isCollection): string;

    /**
     * @param string|int $id
     * @return RateInterface[]
     */
    abstract protected function importTestRates($id): array;

    public function testAddRateMissingEntityAction()
    {
        $data = [
            'user' => 1,
            'rate' => 12.34,
            'internal_rate' => 6.66,
            'is_fixed' => false
        ];
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertEntityNotFoundForPost($client, $this->getRateUrl(99), $data);
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
        $this->assertApiResponseAccessDenied($response, 'Access denied.');
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

    public function testGetRatesEmptyResult()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->request($client, $this->getRateUrl(1));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetRates()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $expectedRates = $this->importTestRates(1);

        $this->request($client, $this->getRateUrl(1));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(\count($expectedRates), \count($result));

        foreach ($result as $rate) {
            $this->assertRateStructure($rate, ($rate['user'] === null ? null : $rate['user']['id']));
        }
    }

    public function testGetRatesEntityNotFound()
    {
        $this->assertEntityNotFound(User::ROLE_ADMIN, $this->getRateUrl(99));
    }

    public function testGetRatesIsSecured()
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, $this->getRateUrl(1));
    }

    public function testDeleteRate()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $expectedRates = $this->importTestRates(1);

        $this->request($client, $this->getRateUrlByRate($expectedRates[0], false), 'DELETE');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEmpty($client->getResponse()->getContent());

        // fetch rates to validate that one was removed
        $this->request($client, $this->getRateUrlByRate($expectedRates[0], true));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertEquals(\count($expectedRates) - 1, \count($result));
    }

    public function testDeleteRateEntityNotFound()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertNotFoundForDelete($client, $this->getRateUrl(99, 1));
    }

    public function testDeleteRateRateNotFound()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertNotFoundForDelete($client, $this->getRateUrl(1, 99));
    }

    public function testDeleteRateWithInvalidAssignment()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->importTestRates(1);
        $this->importTestRates(2);

        $this->assertNotFoundForDelete($client, $this->getRateUrl(2, 1));
    }

    public function testDeleteNotAllowed()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $rates = $this->importTestRates(1);

        /** @var ActivityRate|ProjectRate|CustomerRate $rate */
        $rate = $rates[0];

        $this->request($client, $this->getRateUrl(1, $rate->getId()), 'DELETE');
        $this->assertApiResponseAccessDenied($client->getResponse(), 'Access denied.');
    }

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
