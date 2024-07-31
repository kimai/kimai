<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\DataFixtures\UserFixtures;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\CustomerMeta;
use App\Entity\CustomerRate;
use App\Entity\Project;
use App\Entity\RateInterface;
use App\Entity\Team;
use App\Entity\User;
use App\Repository\CustomerRateRepository;
use App\Repository\CustomerRepository;
use App\Tests\Mocks\CustomerTestMetaFieldSubscriberMock;

/**
 * @group integration
 */
class CustomerControllerTest extends APIControllerBaseTest
{
    use RateControllerTestTrait;

    /**
     * @param CustomerRate $rate
     * @param bool $isCollection
     * @return string
     */
    protected function getRateUrlByRate(RateInterface $rate, bool $isCollection): string
    {
        if ($isCollection) {
            return $this->getRateUrl($rate->getCustomer()->getId());
        }

        return $this->getRateUrl($rate->getCustomer()->getId(), $rate->getId());
    }

    protected function getRateUrl($id = '1', $rateId = null): string
    {
        if (null !== $rateId) {
            return sprintf('/api/customers/%s/rates/%s', $id, $rateId);
        }

        return sprintf('/api/customers/%s/rates', $id);
    }

    protected function importTestRates($id): array
    {
        /** @var CustomerRateRepository $rateRepository */
        $rateRepository = $this->getEntityManager()->getRepository(CustomerRate::class);
        /** @var CustomerRepository $repository */
        $repository = $this->getEntityManager()->getRepository(Customer::class);
        /** @var Customer|null $customer */
        $customer = $repository->find($id);

        if (null === $customer) {
            $customer = new Customer('foooo');
            $customer->setCountry('DE');
            $customer->setTimezone('Europre/Paris');
            $repository->saveCustomer($customer);
        }

        $rate1 = new CustomerRate();
        $rate1->setCustomer($customer);
        $rate1->setRate(17.45);
        $rate1->setIsFixed(false);

        $rateRepository->saveRate($rate1);

        $rate2 = new CustomerRate();
        $rate2->setCustomer($customer);
        $rate2->setRate(99);
        $rate2->setInternalRate(9);
        $rate2->setIsFixed(true);
        $rate2->setUser($this->getUserByName(UserFixtures::USERNAME_USER));

        $rateRepository->saveRate($rate2);

        return [$rate1, $rate2];
    }

    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/customers');
    }

    public function testGetCollection(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/customers');

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $result = json_decode($content, true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(1, \count($result));
        self::assertApiResponseTypeStructure('CustomerCollection', $result[0]);
    }

    public function testGetCollectionWithQuery(): void
    {
        $query = ['order' => 'ASC', 'orderBy' => 'name', 'visible' => 3, 'term' => 'test'];
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/customers', 'GET', $query);

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $result = json_decode($content, true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(1, \count($result));
        self::assertApiResponseTypeStructure('CustomerCollection', $result[0]);
    }

    public function testGetEntityIsSecure(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/api/customers/1');
    }

    public function testGetEntity(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/api/customers/1');

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $result = json_decode($content, true);

        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('CustomerEntity', $result);
    }

    public function testGetEntityWithFullResponse(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $em = $this->getEntityManager();

        /** @var Customer $customer */
        $customer = $em->getRepository(Customer::class)->find(1);

        // add meta fields
        $meta = new CustomerMeta();
        $meta->setName('bar')->setValue('foo')->setIsVisible(false);
        $customer->setMetaField($meta);
        $meta = new CustomerMeta();
        $meta->setName('foo')->setValue('bar')->setIsVisible(true);
        $customer->setMetaField($meta);
        $em->persist($customer);

        // add a new project ...
        $project = new Project();
        $project->setName('Activity Test');
        $project->setCustomer($customer);
        $em->persist($project);

        // ... with activity
        $activity = (new Activity())->setName('first one')->setComment('1')->setProject($project);
        $em->persist($activity);

        // and finally a team
        $team = new Team('Testing customer 1 team');
        $team->addTeamlead($this->getUserByRole(User::ROLE_USER));
        $team->addCustomer($customer);
        $team->addProject($project);
        $team->addUser($this->getUserByRole(User::ROLE_TEAMLEAD));
        $em->persist($team);
        $em->flush();

        $this->assertAccessIsGranted($client, '/api/customers/1');

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $result = json_decode($content, true);

        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('CustomerEntity', $result);
    }

    public function testNotFound(): void
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/customers/' . PHP_INT_MAX, 'GET', 'App\\Entity\\Customer object not found by the @ParamConverter annotation.');
    }

    public function testPostAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'visible' => true,
            'country' => 'DE',
            'currency' => 'EUR',
            'timezone' => 'Europe/Berlin',
            'budget' => '999',
            'timeBudget' => '7200',
        ];
        $this->request($client, '/api/customers', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $result = json_decode($content, true);

        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('CustomerEntity', $result);
        $this->assertNotEmpty($result['id']);
    }

    public function testPostActionWithLeastFields(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'country' => 'DE',
            'currency' => 'EUR',
            'timezone' => 'Europe/Berlin',
        ];
        $this->request($client, '/api/customers', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $result = json_decode($content, true);

        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('CustomerEntity', $result);
        $this->assertNotEmpty($result['id']);
    }

    public function testPostActionWithInvalidUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $data = [
            'name' => 'foo',
            'visible' => true,
            'country' => 'DE',
            'currency' => 'EUR',
            'timezone' => 'Europe/Berlin',
        ];
        $this->request($client, '/api/customers', 'POST', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertApiResponseAccessDenied($response, 'User cannot create customers');
    }

    public function testPostActionWithInvalidData(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'visible' => true,
            'country' => 'XYZ',
            'currency' => '---',
            'timezone' => 'foo/bar',
            'unexpected' => 'field',
        ];
        $this->request($client, '/api/customers', 'POST', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertApiCallValidationError($response, ['country', 'currency', 'timezone'], true);
    }

    public function testPatchAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'comment' => '',
            'visible' => true,
            'country' => 'DE',
            'currency' => 'EUR',
            'timezone' => 'Europe/Berlin',
            'budget' => '999',
            'timeBudget' => '7200',
        ];
        $this->request($client, '/api/customers/1', 'PATCH', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $result = json_decode($content, true);

        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('CustomerEntity', $result);
        $this->assertNotEmpty($result['id']);
    }

    public function testPatchActionWithInvalidUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $data = [
            'name' => 'foo',
            'comment' => '',
            'visible' => true,
            'country' => 'DE',
            'currency' => 'EUR',
            'timezone' => 'Europe/Berlin',
        ];
        $this->request($client, '/api/customers/1', 'PATCH', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertApiResponseAccessDenied($response, 'User cannot update customer');
    }

    public function testPatchActionWithUnknownActivity(): void
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_USER, '/api/customers/255', []);
    }

    public function testInvalidPatchAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'visible' => true,
            'country' => 'DE',
            'currency' => 'XXX',
            'timezone' => 'Europe/Berlin',
        ];
        $this->request($client, '/api/customers/1', 'PATCH', [], json_encode($data));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['currency']);
    }

    public function testMetaActionNotAllowed(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/api/customers/1/meta', 'PATCH', [], json_encode(['name' => 'asdasd']));
        $this->assertApiResponseAccessDenied($client->getResponse(), 'You are not allowed to update this customer');
    }

    public function testMetaActionThrowsNotFound(): void
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_ADMIN, '/api/customers/42/meta', []);
    }

    public function testMetaActionThrowsExceptionOnMissingName(): void
    {
        $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/customers/1/meta', ['value' => 'X'], [
            'code' => 400,
            'message' => 'Bad Request'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingValue(): void
    {
        $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/customers/1/meta', ['name' => 'X'], [
            'code' => 400,
            'message' => 'Bad Request'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingMetafield(): void
    {
        $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/customers/1/meta', ['name' => 'X', 'value' => 'Y'], [
            'code' => 404,
            'message' => 'Not Found'
        ]);
    }

    public function testMetaAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        self::getContainer()->get('event_dispatcher')->addSubscriber(new CustomerTestMetaFieldSubscriberMock());

        $data = [
            'name' => 'metatestmock',
            'value' => 'another,testing,bar'
        ];
        $this->request($client, '/api/customers/1/meta', 'PATCH', [], json_encode($data));

        $this->assertTrue($client->getResponse()->isSuccessful());

        $em = $this->getEntityManager();
        /** @var Customer $customer */
        $customer = $em->getRepository(Customer::class)->find(1);
        $this->assertEquals('another,testing,bar', $customer->getMetaField('metatestmock')->getValue());
    }
}
