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
    abstract protected function getRateUrl(string|int $id = '1', string|int|null $rateId = null): string;

    abstract protected function getRateUrlByRate(RateInterface $rate, bool $isCollection): string;

    /**
     * @return RateInterface[]
     */
    abstract protected function importTestRates(string|int $id): array;

    public function testAddRateMissingEntityAction(): void
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

    public function testAddRateMissingUserAction(): void
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
        self::assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['user']);
    }

    public function testAddRateActionWithInvalidUser(): void
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

    public function testAddRateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'user' => null,
            'rate' => 12.34,
            'internal_rate' => 6.66,
            'is_fixed' => false
        ];
        $this->request($client, $this->getRateUrl(), 'POST', [], json_encode($data));
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        $this->assertRateStructure($result, null);
        self::assertNotEmpty($result['id']);
        self::assertEquals(12.34, $result['rate']);
        self::assertEquals(6.66, $result['internalRate']);
        self::assertFalse($result['isFixed']);
    }

    public function testAddFixedRateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'user' => 1,
            'rate' => 12.34,
            'internal_rate' => 6.66,
            'is_fixed' => true
        ];
        $this->request($client, $this->getRateUrl(), 'POST', [], json_encode($data));
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        $this->assertRateStructure($result, 1);
        self::assertNotEmpty($result['id']);
        self::assertEquals(12.34, $result['rate']);
        self::assertEquals(6.66, $result['internalRate']);
        self::assertTrue($result['isFixed']);
    }

    public function testGetRatesEmptyResult(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->request($client, $this->getRateUrl(1));
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testGetRates(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $expectedRates = $this->importTestRates(1);

        $this->request($client, $this->getRateUrl(1));
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(\count($expectedRates), \count($result));

        foreach ($result as $rate) {
            self::assertIsArray($rate);
            if ($rate['user'] === null) {
                $this->assertRateStructure($rate);
            } else {
                self::assertIsArray($rate['user']);
                $this->assertRateStructure($rate, $rate['user']['id']);
            }
        }
    }

    public function testGetRatesEntityNotFound(): void
    {
        $this->assertEntityNotFound(User::ROLE_ADMIN, $this->getRateUrl(99));
    }

    public function testGetRatesIsSecured(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, $this->getRateUrl(1));
    }

    public function testDeleteRate(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $expectedRates = $this->importTestRates(1);

        $this->request($client, $this->getRateUrlByRate($expectedRates[0], false), 'DELETE');
        self::assertTrue($client->getResponse()->isSuccessful());
        self::assertEmpty($client->getResponse()->getContent());

        // fetch rates to validate that one was removed
        $this->request($client, $this->getRateUrlByRate($expectedRates[0], true));
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertEquals(\count($expectedRates) - 1, \count($result));
    }

    public function testDeleteRateEntityNotFound(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertNotFoundForDelete($client, $this->getRateUrl(99, 1));
    }

    public function testDeleteRateRateNotFound(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertNotFoundForDelete($client, $this->getRateUrl(1, 99));
    }

    public function testDeleteRateWithInvalidAssignment(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->importTestRates(1);
        $this->importTestRates(2);

        $this->assertNotFoundForDelete($client, $this->getRateUrl(2, 1));
    }

    public function testDeleteNotAllowed(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $rates = $this->importTestRates(1);

        /** @var ActivityRate|ProjectRate|CustomerRate $rate */
        $rate = $rates[0];

        $this->request($client, $this->getRateUrl(1, $rate->getId()), 'DELETE');
        $this->assertApiResponseAccessDenied($client->getResponse(), 'Access denied.');
    }

    public function assertRateStructure(array $result, $user = null): void
    {
        $expectedKeys = [
            'id', 'rate', 'internalRate', 'isFixed', 'user'
        ];

        $actual = array_keys($result);
        sort($actual);
        sort($expectedKeys);

        self::assertEquals($expectedKeys, $actual, 'Rate structure does not match');

        if (null !== $user) {
            self::assertIsArray($result['user'], 'Rate user is not an array');
            self::assertEquals($user, $result['user']['id'], 'Rate user does not match');
        } else {
            self::assertNull($result['user']);
        }
    }
}
