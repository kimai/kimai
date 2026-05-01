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
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('integration')]
class CustomerControllerTest extends APIControllerBaseTestCase
{
    use RateControllerTestTrait;

    protected function getRateUrlByRate(RateInterface $rate, bool $isCollection): string
    {
        self::assertInstanceOf(CustomerRate::class, $rate);
        self::assertNotNull($rate->getCustomer());
        self::assertNotNull($rate->getCustomer()->getId());

        if ($isCollection) {
            return $this->getRateUrl($rate->getCustomer()->getId());
        }

        return $this->getRateUrl($rate->getCustomer()->getId(), $rate->getId());
    }

    protected function getRateUrl(?int $id = 1, ?int $rateId = null): string
    {
        if (null !== $rateId) {
            return \sprintf('/api/customers/%s/rates/%s', $id, $rateId);
        }

        return \sprintf('/api/customers/%s/rates', $id);
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

    /**
     * @return array{0: Customer, 1: Customer}
     */
    private function loadCustomerData(): array
    {
        /** @var CustomerRateRepository $rateRepository */
        $rateRepository = $this->getEntityManager()->getRepository(CustomerRate::class);
        /** @var CustomerRepository $repository */
        $repository = $this->getEntityManager()->getRepository(Customer::class);

        $customer1 = new Customer('foooo');
        $customer1->setCountry('DE');
        $customer1->setTimezone('Europe/Paris');
        $repository->saveCustomer($customer1);

        $customer2 = new Customer('baaaaar');
        $customer2->setCountry('RU');
        $customer2->setTimezone('Europe/Moscow');
        $repository->saveCustomer($customer2);

        $rate1 = new CustomerRate();
        $rate1->setCustomer($customer1);
        $rate1->setRate(17.45);
        $rate1->setIsFixed(false);

        $rateRepository->saveRate($rate1);

        $rate2 = new CustomerRate();
        $rate2->setCustomer($customer1);
        $rate2->setRate(99);
        $rate2->setInternalRate(9);
        $rate2->setIsFixed(true);
        $rate2->setUser($this->getUserByName(UserFixtures::USERNAME_USER));

        $rateRepository->saveRate($rate2);

        return [$customer1, $customer2];
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
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(1, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('CustomerCollection', $result[0]);
    }

    public function testGetCollectionWithQuery(): void
    {
        $query = ['order' => 'ASC', 'orderBy' => 'name', 'visible' => 3, 'term' => 'test'];
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/customers', 'GET', $query);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(1, \count($result));
        self::assertIsArray($result[0]);
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
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
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
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('CustomerEntity', $result);

        self::assertNotEmpty($result['id']);
        self::assertIsArray($result['teams']);
        self::assertCount(1, $result['teams']);
        self::assertIsArray($result['teams'][0]);
        self::assertEquals('Testing customer 1 team', $result['teams'][0]['name']);
        self::assertIsArray($result['metaFields']);
        self::assertCount(1, $result['metaFields']);
        self::assertEquals([['name' => 'foo', 'value' => 'bar']], $result['metaFields']);
        self::assertEquals('Test', $result['name']);
        self::assertEquals(1000, $result['budget']);
        self::assertEquals(100000, $result['timeBudget']);
        self::assertNull($result['budgetType']);
        self::assertEquals('1', $result['number']);
        self::assertEquals('Test comment', $result['comment']);
        self::assertEquals('Test', $result['company']);
        self::assertNull($result['vatId']);
        self::assertEquals('Test', $result['contact']);
        self::assertEquals('Test', $result['address']);
        self::assertNull($result['addressLine1']);
        self::assertNull($result['addressLine2']);
        self::assertNull($result['addressLine3']);
        self::assertNull($result['postCode']);
        self::assertNull($result['city']);
        self::assertEquals('DE', $result['country']);
        self::assertEquals('EUR', $result['currency']);
        self::assertEquals('111', $result['phone']);
        self::assertEquals('222', $result['fax']);
        self::assertEquals('333', $result['mobile']);
        self::assertEquals('test@example.com', $result['email']);
        self::assertNull($result['homepage']);
        self::assertEquals('Europe/Berlin', $result['timezone']);
        self::assertNull($result['buyerReference']);
        self::assertNull($result['color']);
        self::assertEquals('#5319e7', $result['color-safe']);
        self::assertTrue($result['visible']);
        self::assertTrue($result['billable']);
    }

    public function testNotFound(): void
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/customers/' . PHP_INT_MAX);
    }

    public function testPostAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'budget' => '999',
            'timeBudget' => '10,25',
            'budgetType' => 'month',
            'number' => 'C-754',
            'comment' => 'Awesome customer since a long time',
            'company' => 'IT Professional Comp.',
            'vatId' => 'DE0123456789',
            'contact' => 'Mr. John Doe',
            'addressLine1' => 'test address line 1',
            'addressLine2' => 'foo address line 2',
            'addressLine3' => 'bar address line 3',
            'postCode' => '12345',
            'city' => 'Acme Town',
            'country' => 'DE',
            'currency' => 'EUR',
            'phone' => '666667787778999909087',
            'fax' => '0987654321',
            'mobile' => '01234534567890',
            'email' => 'admin@example.com',
            'homepage' => 'https://www.example.com/',
            'timezone' => 'Europe/Berlin',
            'invoiceText' => 'Some random text, pay fast please!',
            'buyerReference' => 'REF-0123456789',
            'color' => '#ff0000',
            'visible' => true,
            'billable' => true,
            'teams' => [1],
        ];
        $this->request($client, '/api/customers', 'POST', [], json_encode($data));
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('CustomerEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertIsArray($result['teams']);
        self::assertEquals([['id' => 1, 'name' => 'Test team', 'color' => null, 'color-safe' => '#03A9F4']], $result['teams']);
        self::assertIsArray($result['metaFields']);
        self::assertEquals([], $result['metaFields']);
        self::assertEquals('foo', $result['name']);
        self::assertEquals('999', $result['budget']);
        self::assertEquals('36900', $result['timeBudget']);
        self::assertEquals('month', $result['budgetType']);
        self::assertEquals('C-754', $result['number']);
        self::assertEquals('Awesome customer since a long time', $result['comment']);
        self::assertEquals('IT Professional Comp.', $result['company']);
        self::assertEquals('DE0123456789', $result['vatId']);
        self::assertEquals('Mr. John Doe', $result['contact']);
        self::assertNull($result['address']);
        self::assertEquals('test address line 1', $result['addressLine1']);
        self::assertEquals('foo address line 2', $result['addressLine2']);
        self::assertEquals('bar address line 3', $result['addressLine3']);
        self::assertEquals('12345', $result['postCode']);
        self::assertEquals('Acme Town', $result['city']);
        self::assertEquals('DE', $result['country']);
        self::assertEquals('EUR', $result['currency']);
        self::assertEquals('666667787778999909087', $result['phone']);
        self::assertEquals('0987654321', $result['fax']);
        self::assertEquals('01234534567890', $result['mobile']);
        self::assertEquals('admin@example.com', $result['email']);
        self::assertEquals('https://www.example.com/', $result['homepage']);
        self::assertEquals('Europe/Berlin', $result['timezone']);
        self::assertEquals('REF-0123456789', $result['buyerReference']);
        self::assertEquals('#ff0000', $result['color']);
        self::assertTrue($result['visible']);
        self::assertTrue($result['billable']);
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
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('CustomerEntity', $result);
        self::assertNotEmpty($result['id']);
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
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('CustomerEntity', $result);
        self::assertNotEmpty($result['id']);
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
        self::assertEquals(400, $response->getStatusCode());
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
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => 'Bad Request'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingValue(): void
    {
        $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/customers/1/meta', ['name' => 'X'], [
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => 'Bad Request'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingMetafield(): void
    {
        $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/customers/1/meta', ['name' => 'X', 'value' => 'Y'], [
            'code' => Response::HTTP_NOT_FOUND,
            'message' => 'Not Found'
        ]);
    }

    public function testMetaAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        /** @var EventDispatcher $dispatcher */
        $dispatcher = static::getContainer()->get('event_dispatcher');
        $dispatcher->addSubscriber(new CustomerTestMetaFieldSubscriberMock());

        $data = [
            'name' => 'metatestmock',
            'value' => 'another,testing,bar'
        ];
        $this->request($client, '/api/customers/1/meta', 'PATCH', [], json_encode($data));

        self::assertTrue($client->getResponse()->isSuccessful());

        $em = $this->getEntityManager();
        /** @var Customer $customer */
        $customer = $em->getRepository(Customer::class)->find(1);
        self::assertEquals('another,testing,bar', $customer->getMetaField('metatestmock')->getValue());
    }

    // ------------------------------- [DELETE] -------------------------------

    public function testDeleteIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/customers/1', Request::METHOD_DELETE);
    }

    public function testDeleteActionWithUnknownTimesheet(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertNotFoundForDelete($client, '/api/customers/' . PHP_INT_MAX);
    }

    public function testDeleteEntityIsSecure(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/api/customers/1', Request::METHOD_DELETE);
    }

    public function testDeleteActionWithoutAuthorization(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $imports = $this->loadCustomerData();

        $this->request($client, '/api/customers/' . $imports[1]->getId(), Request::METHOD_DELETE);

        $response = $client->getResponse();
        $this->assertApiResponseAccessDenied($response);
    }

    public function testDeleteAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $imports = $this->loadCustomerData();
        $getUrl = '/api/customers/' . $imports[0]->getId();
        $this->assertAccessIsGranted($client, $getUrl);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('CustomerEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertIsNumeric($result['id']);
        $id = $result['id'];

        $this->request($client, '/api/customers/' . $id, Request::METHOD_DELETE);
        self::assertTrue($client->getResponse()->isSuccessful());
        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        self::assertEmpty($client->getResponse()->getContent());

        $this->request($client, $getUrl);
        $this->assertApiException($client->getResponse(), [
            'code' => Response::HTTP_NOT_FOUND,
            'message' => 'Not Found'
        ]);
    }
}
