<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\DataFixtures\UserFixtures;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\ProjectMeta;
use App\Entity\ProjectRate;
use App\Entity\RateInterface;
use App\Entity\Team;
use App\Entity\User;
use App\Repository\ProjectRateRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\VisibilityInterface;
use App\Tests\Mocks\ProjectTestMetaFieldSubscriberMock;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * @group integration
 */
class ProjectControllerTest extends APIControllerBaseTest
{
    use RateControllerTestTrait;

    /**
     * @param ProjectRate $rate
     * @param bool $isCollection
     * @return string
     */
    protected function getRateUrlByRate(RateInterface $rate, bool $isCollection): string
    {
        if ($isCollection) {
            return $this->getRateUrl($rate->getProject()->getId());
        }

        return $this->getRateUrl($rate->getProject()->getId(), $rate->getId());
    }

    protected function getRateUrl($id = '1', $rateId = null): string
    {
        if (null !== $rateId) {
            return sprintf('/api/projects/%s/rates/%s', $id, $rateId);
        }

        return sprintf('/api/projects/%s/rates', $id);
    }

    protected function importTestRates($id): array
    {
        /** @var ProjectRateRepository $rateRepository */
        $rateRepository = $this->getEntityManager()->getRepository(ProjectRate::class);
        /** @var ProjectRepository $repository */
        $repository = $this->getEntityManager()->getRepository(Project::class);
        /** @var Project|null $project */
        $project = $repository->find($id);

        if (null === $project) {
            $project = new Project();
            $project->setName('foooo');
            $project->setCustomer($this->getEntityManager()->getRepository(Customer::class)->find(1));
            $repository->saveProject($project);
        }

        $rate1 = new ProjectRate();
        $rate1->setProject($project);
        $rate1->setRate(17.45);
        $rate1->setIsFixed(false);

        $rateRepository->saveRate($rate1);

        $rate2 = new ProjectRate();
        $rate2->setProject($project);
        $rate2->setRate(99);
        $rate2->setInternalRate(9);
        $rate2->setIsFixed(true);
        $rate2->setUser($this->getUserByName(UserFixtures::USERNAME_USER));

        $rateRepository->saveRate($rate2);

        return [$rate1, $rate2];
    }

    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/api/projects');
    }

    public function testGetCollection()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/projects');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(1, \count($result));
        self::assertApiResponseTypeStructure('ProjectCollection', $result[0]);
    }

    protected function loadProjectTestData(HttpKernelBrowser $client)
    {
        $em = $this->getEntityManager();

        $customer = $em->getRepository(Customer::class)->find(1);

        $customer2 = new Customer('first one');
        $customer2->setVisible(false);
        $customer2->setCountry('de');
        $customer2->setTimezone('Europe/Berlin');
        $em->persist($customer2);

        $customer3 = new Customer('second one');
        $customer3->setCountry('at');
        $customer3->setTimezone('Europe/Vienna');
        $em->persist($customer3);

        $project = new Project();
        $project->setName('first');
        $project->setVisible(false);
        $project->setCustomer($customer2);
        $em->persist($project);

        $project = new Project();
        $project->setName('second');
        $project->setVisible(false);
        $project->setCustomer($customer);
        $em->persist($project);

        $project = new Project();
        $project->setName('third');
        $project->setVisible(true);
        $project->setCustomer($customer2);
        $em->persist($project);

        $project = new Project();
        $project->setName('fourth');
        $project->setVisible(true);
        $project->setCustomer($customer3);
        $em->persist($project);

        $project = new Project();
        $project->setName('fifth');
        $project->setVisible(true);
        $project->setCustomer($customer);

        // add meta fields
        $meta = new ProjectMeta();
        $meta->setName('bar')->setValue('foo')->setIsVisible(false);
        $project->setMetaField($meta);
        $meta = new ProjectMeta();
        $meta->setName('foo')->setValue('bar')->setIsVisible(true);
        $project->setMetaField($meta);
        $em->persist($project);

        // and a team
        $team = new Team('Testing project team');
        $team->addTeamlead($this->getUserByRole(User::ROLE_USER));
        $team->addCustomer($customer);
        $team->addProject($project);
        $team->addUser($this->getUserByRole(User::ROLE_TEAMLEAD));
        $em->persist($team);

        $project = (new Project())->setName('sixth')->setVisible(false)->setCustomer($customer3);
        $em->persist($project);

        $em->flush();

        return [$customer, $customer2, $customer3];
    }

    /**
     * @dataProvider getCollectionTestData
     */
    public function testGetCollectionWithParams($url, $customer, $parameters, $expected): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $imports = $this->loadProjectTestData($client);

        $customerId = $customer !== null ? $imports[$customer]->getId() : null;

        if ($customerId !== null) {
            if (\array_key_exists('customer', $parameters)) {
                $parameters['customer'] = $customerId;
            }
            if (\array_key_exists('customers', $parameters)) {
                if (!\is_array($parameters['customers'])) {
                    throw new \InvalidArgumentException('customers needs to be an array');
                }
                $count = \count($parameters['customers']);
                if ($count === 2) {
                    $parameters['customers'] = [$customerId, $customerId];
                } elseif ($count === 1) {
                    $parameters['customers'] = [$customerId];
                } else {
                    throw new \InvalidArgumentException('Invalid count for customers');
                }
            }
        }

        $this->assertAccessIsGranted($client, $url, 'GET', $parameters);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertEquals(\count($expected), \count($result), 'Found wrong amount of projects');

        for ($i = 0; $i < \count($expected); $i++) {
            $project = $result[$i];
            self::assertApiResponseTypeStructure('ProjectCollection', $project);
            if ($customerId !== null) {
                $this->assertEquals($customerId, $project['customer']);
            }
        }
    }

    /**
     * @return \Generator<array<mixed>>
     */
    public function getCollectionTestData(): iterable
    {
        // if you wonder why: case-sensitive ordering feels strange ... "Title" > "fifth”
        yield ['/api/projects', null, [], [[true, 1], [false, 1], [false, 3]]];
        yield ['/api/projects', 0, ['customer' => '1'], [[true, 1], [false, 1]]];
        yield ['/api/projects', 0, ['customer' => '1', 'visible' => VisibilityInterface::SHOW_VISIBLE], [[true, 1], [false, 1]]];
        yield ['/api/projects', 0, ['customer' => '1', 'visible' => VisibilityInterface::SHOW_BOTH], [[true, 1], [false, 1], [false, 1]]];
        yield ['/api/projects', 0, ['customer' => '1', 'visible' => VisibilityInterface::SHOW_HIDDEN], [[false, 1]]];
        // customer is invisible => query only returns results for VisibilityInterface::SHOW_BOTH
        yield ['/api/projects', 1, ['customer' => '2', 'visible' => VisibilityInterface::SHOW_VISIBLE], []];
        yield ['/api/projects', 1, ['customer' => '2', 'visible' => VisibilityInterface::SHOW_BOTH], [[false, 2], [false, 2]]];
        yield ['/api/projects', 1, ['customer' => '2', 'customers' => ['2'], 'visible' => VisibilityInterface::SHOW_BOTH], [[false, 2], [false, 2]]];
        yield ['/api/projects', 1, ['customers' => ['2', '2'], 'visible' => VisibilityInterface::SHOW_BOTH], [[false, 2], [false, 2]]];
        yield ['/api/projects', 1, ['customer' => '2', 'visible' => VisibilityInterface::SHOW_HIDDEN], []];
        yield ['/api/projects', 1, ['customer' => '2', 'visible' => VisibilityInterface::SHOW_HIDDEN, 'start' => '2010-12-11', 'end' => '2030-12-11'], []];
        yield ['/api/projects', 1, ['customers' => ['2'], 'visible' => VisibilityInterface::SHOW_HIDDEN, 'start' => '2010-12-11', 'end' => '2030-12-11'], []];
        yield ['/api/projects', 1, ['customers' => ['2', '2'], 'visible' => VisibilityInterface::SHOW_HIDDEN, 'start' => '2010-12-11', 'end' => '2030-12-11'], []];
    }

    public function testGetEntity()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $em = $this->getEntityManager();

        $customer = new Customer('first one');
        $customer->setVisible(true);
        $customer->setCountry('de');
        $customer->setTimezone('Europe/Berlin');
        $em->persist($customer);

        $orderDate = new \DateTime('2019-11-29 14:35:17', new \DateTimeZone('Pacific/Tongatapu'));
        $startDate = new \DateTime('2020-01-07 18:19:20', new \DateTimeZone('Pacific/Tongatapu'));
        $endDate = new \DateTime('2021-03-23 00:00:01', new \DateTimeZone('Pacific/Tongatapu'));

        $project = new Project();
        $project->setName('first');
        $project->setVisible(true);
        $project->setCustomer($customer);
        $project->setOrderDate($orderDate);
        $project->setStart($startDate);
        $project->setEnd($endDate);
        $em->persist($project);
        $em->flush();

        $this->assertAccessIsGranted($client, '/api/projects/' . $project->getId());
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('ProjectEntity', $result);

        $expected = [
            'parentTitle' => 'first one',
            'customer' => $customer->getId(),
            'id' => $project->getId(),
            'name' => 'first',
            'orderNumber' => null,
            // make sure the timezone is properly applied in serializer (see #1858)
            'orderDate' => '2019-11-29',
            'start' => '2020-01-07',
            'end' => '2021-03-23',
            'comment' => null,
            'visible' => true,
            'budget' => 0.0,
            'timeBudget' => 0,
            'metaFields' => [],
            'teams' => [],
            'color' => null,
        ];

        foreach ($expected as $key => $value) {
            self::assertEquals($value, $result[$key]);
        }
    }

    public function testNotFound()
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/projects/' . PHP_INT_MAX, 'GET', 'App\\Entity\\Project object not found by the @ParamConverter annotation.');
    }

    public function testPostAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'customer' => 1,
            'orderDate' => '2018-04-17',
            'start' => '2019-02-01',
            'end' => '2020-02-08',
            'budget' => '999',
            'timeBudget' => '7200',
            'orderNumber' => '1234567890/WXYZ/SUBPROJECT/1234/CONTRACT/EMPLOYEE1',
        ];
        $this->request($client, '/api/projects', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('ProjectEntity', $result);
        $this->assertNotEmpty($result['id']);
        self::assertEquals('2018-04-17', $result['orderDate']);
        self::assertEquals('2019-02-01', $result['start']);
        self::assertEquals('2020-02-08', $result['end']);
        self::assertEquals('1234567890/WXYZ/SUBPROJECT/1234/CONTRACT/EMPLOYEE1', $result['orderNumber']);
        self::assertFalse($result['globalActivities']);
        self::assertFalse($result['billable']);
        self::assertFalse($result['visible']);
    }

    public function testPostActionWithOtherFields()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'customer' => 1,
            'globalActivities' => true,
            'billable' => 1,
            'visible' => '',
        ];
        $this->request($client, '/api/projects', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('ProjectEntity', $result);
        $this->assertNotEmpty($result['id']);
        self::assertEquals('foo', $result['name']);
        self::assertTrue($result['globalActivities']);
        self::assertTrue($result['billable']);
        self::assertTrue($result['visible']);
    }

    public function testPostActionWithOtherFieldsAndFalse()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'customer' => 1,
            'globalActivities' => false,
            'billable' => false,
            'visible' => false,
        ];
        $this->request($client, '/api/projects', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('ProjectEntity', $result);
        $this->assertNotEmpty($result['id']);
        self::assertEquals('foo', $result['name']);
        self::assertFalse($result['globalActivities']);
        self::assertFalse($result['billable']);
        self::assertFalse($result['visible']);
    }

    public function testPostActionWithOtherFields3()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'customer' => 1,
            'globalActivities' => true,
            'billable' => true,
            'visible' => true,
        ];
        $this->request($client, '/api/projects', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('ProjectEntity', $result);
        $this->assertNotEmpty($result['id']);
        self::assertEquals('foo', $result['name']);
        self::assertTrue($result['globalActivities']);
        self::assertTrue($result['billable']);
        self::assertTrue($result['visible']);
    }

    public function testPostActionWithLeastFields()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'customer' => 1
        ];
        $this->request($client, '/api/projects', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('ProjectEntity', $result);
        $this->assertNotEmpty($result['id']);
        self::assertEquals('foo', $result['name']);
        self::assertFalse($result['globalActivities']);
        self::assertFalse($result['billable']);
        self::assertFalse($result['visible']);
    }

    public function testPostActionWithInvalidUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $data = [
            'name' => 'foo',
            'customer' => 1,
            'visible' => true,
        ];
        $this->request($client, '/api/projects', 'POST', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertApiResponseAccessDenied($response, 'User cannot create projects');
    }

    public function testPostActionWithInvalidData()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'customer' => 100,
            'xxxxx' => 'whoami',
            'visible' => true
        ];
        $this->request($client, '/api/projects', 'POST', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertApiCallValidationError($response, ['customer'], true);
    }

    public function testPatchAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'comment' => '',
            'customer' => 1,
            'visible' => true,
            'budget' => '999',
            'timeBudget' => '7200',
        ];
        $this->request($client, '/api/projects/1', 'PATCH', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('ProjectEntity', $result);
        $this->assertNotEmpty($result['id']);
    }

    public function testPatchActionWithInvalidUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $data = [
            'name' => 'foo',
            'comment' => '',
            'customer' => 1,
            'visible' => true
        ];
        $this->request($client, '/api/projects/1', 'PATCH', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertApiResponseAccessDenied($response, 'User cannot update project');
    }

    public function testPatchActionWithUnknownActivity()
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_USER, '/api/projects/255', []);
    }

    public function testInvalidPatchAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'customer' => 255,
            'visible' => true
        ];
        $this->request($client, '/api/projects/1', 'PATCH', [], json_encode($data));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['customer']);
    }

    public function testMetaActionThrowsNotFound()
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_ADMIN, '/api/projects/42/meta', []);
    }

    public function testMetaActionThrowsExceptionOnMissingName()
    {
        $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/projects/1/meta', ['value' => 'X'], [
            'code' => 400,
            'message' => 'Bad Request'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingValue()
    {
        $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/projects/1/meta', ['name' => 'X'], [
            'code' => 400,
            'message' => 'Bad Request'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingMetafield()
    {
        $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/projects/1/meta', ['name' => 'X', 'value' => 'Y'], [
            'code' => 404,
            'message' => 'Not Found'
        ]);
    }

    public function testMetaAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        self::getContainer()->get('event_dispatcher')->addSubscriber(new ProjectTestMetaFieldSubscriberMock());

        $data = [
            'name' => 'metatestmock',
            'value' => 'another,testing,bar'
        ];
        $this->request($client, '/api/projects/1/meta', 'PATCH', [], json_encode($data));

        $this->assertTrue($client->getResponse()->isSuccessful());

        $em = $this->getEntityManager();
        /** @var Project $project */
        $project = $em->getRepository(Project::class)->find(1);
        $this->assertEquals('another,testing,bar', $project->getMetaField('metatestmock')->getValue());
    }
}
