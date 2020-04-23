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
use App\Entity\ProjectRate;
use App\Entity\User;
use App\Repository\ProjectRateRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\VisibilityInterface;
use App\Tests\Mocks\ProjectTestMetaFieldSubscriberMock;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * @group integration
 */
class ProjectControllerTest extends APIControllerBaseTest
{
    use RateControllerTestTrait;

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
        $this->assertStructure($result[0], false);
    }

    protected function loadProjectTestData(HttpKernelBrowser $client)
    {
        $em = $this->getEntityManager();

        $customer = $em->getRepository(Customer::class)->find(1);

        $customer2 = (new Customer())->setName('first one')->setVisible(false)->setCountry('de')->setTimezone('Europe/Berlin');
        $em->persist($customer2);

        $customer3 = (new Customer())->setName('second one')->setCountry('at')->setTimezone('Europe/Vienna');
        $em->persist($customer3);

        $project = (new Project())->setName('first')->setVisible(false)->setCustomer($customer2);
        $em->persist($project);

        $project = (new Project())->setName('second')->setVisible(false)->setCustomer($customer);
        $em->persist($project);

        $project = (new Project())->setName('third')->setVisible(true)->setCustomer($customer2);
        $em->persist($project);

        $project = (new Project())->setName('fourth')->setVisible(true)->setCustomer($customer3);
        $em->persist($project);

        $project = (new Project())->setName('fifth')->setVisible(true)->setCustomer($customer);
        $em->persist($project);

        $project = (new Project())->setName('sixth')->setVisible(false)->setCustomer($customer3);
        $em->persist($project);

        $em->flush();
    }

    /**
     * @dataProvider getCollectionTestData
     */
    public function testGetCollectionWithParams($url, $parameters, $expected)
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->loadProjectTestData($client);
        $this->assertAccessIsGranted($client, $url, 'GET', $parameters);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertEquals(\count($expected), \count($result), 'Found wrong amount of projects');

        for ($i = 0; $i < \count($expected); $i++) {
            $project = $result[$i];
            $compare = $expected[$i];
            $this->assertStructure($project, false);
            $this->assertEquals($compare[1], $project['customer']);
        }
    }

    public function getCollectionTestData()
    {
        // if you wonder why: SQLite does case-sensitive ordering, so "Title" > "fifth”
        yield ['/api/projects', [], [[true, 1], [false, 1], [false, 3]]];
        yield ['/api/projects', ['customer' => '1'], [[true, 1], [false, 1]]];
        yield ['/api/projects', ['customer' => '1', 'visible' => VisibilityInterface::SHOW_VISIBLE], [[true, 1], [false, 1]]];
        yield ['/api/projects', ['customer' => '1', 'visible' => VisibilityInterface::SHOW_BOTH], [[true, 1], [false, 1], [false, 1]]];
        yield ['/api/projects', ['customer' => '1', 'visible' => VisibilityInterface::SHOW_HIDDEN], [[false, 1]]];
        // customer is invisible, so nothing should be returned
        yield ['/api/projects', ['customer' => '2', 'visible' => VisibilityInterface::SHOW_VISIBLE], []];
        yield ['/api/projects', ['customer' => '2', 'visible' => VisibilityInterface::SHOW_BOTH], [[false, 2], [false, 2]]];
        yield ['/api/projects', ['customer' => '2', 'customers' => '2', 'visible' => VisibilityInterface::SHOW_BOTH], [[false, 2], [false, 2]]];
        yield ['/api/projects', ['customer' => '2', 'customers' => '2,2', 'visible' => VisibilityInterface::SHOW_BOTH], [[false, 2], [false, 2]]];
        // customer is invisible, so nothing should be returned
        yield ['/api/projects', ['customer' => '2', 'visible' => VisibilityInterface::SHOW_HIDDEN, 'start' => '2010-12-11T23:59:59', 'end' => '2030-12-11T23:59:59'], []];
        yield ['/api/projects', ['customers' => '2', 'visible' => VisibilityInterface::SHOW_HIDDEN, 'start' => '2010-12-11T23:59:59', 'end' => '2030-12-11T23:59:59'], []];
        yield ['/api/projects', ['customers' => '2,2', 'visible' => VisibilityInterface::SHOW_HIDDEN, 'start' => '2010-12-11T23:59:59', 'end' => '2030-12-11T23:59:59'], []];
    }

    public function testGetEntity()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/projects/1');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertStructure($result);
    }

    public function testNotFound()
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/projects/2');
    }

    public function testPostAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'customer' => 1,
            'visible' => true,
            'orderDate' => '2018-02-08T13:02:54',
            'start' => '2019-02-01T19:32:17',
            'end' => '2020-02-08T21:11:42',
            'budget' => '999',
            'timeBudget' => '7200',
        ];
        $this->request($client, '/api/projects', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertStructure($result);
        $this->assertNotEmpty($result['id']);
        self::assertEquals('2018-02-08T13:02:54+0000', $result['orderDate']);
        self::assertEquals('2019-02-01T19:32:17+0000', $result['start']);
        self::assertEquals('2020-02-08T21:11:42+0000', $result['end']);
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
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('User cannot create projects', $json['message']);
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
        $this->assertStructure($result);
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
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('User cannot update project', $json['message']);
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
        return $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/projects/1/meta', ['value' => 'X'], [
            'code' => 400,
            'message' => 'Parameter "name" of value "NULL" violated a constraint "This value should not be null."'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingValue()
    {
        return $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/projects/1/meta', ['name' => 'X'], [
            'code' => 400,
            'message' => 'Parameter "value" of value "NULL" violated a constraint "This value should not be null."'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingMetafield()
    {
        return $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/projects/1/meta', ['name' => 'X', 'value' => 'Y'], [
            'code' => 500,
            'message' => 'Unknown meta-field requested'
        ]);
    }

    public function testMetaAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        static::$kernel->getContainer()->get('event_dispatcher')->addSubscriber(new ProjectTestMetaFieldSubscriberMock());

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

    protected function assertStructure(array $result, $full = true)
    {
        $expectedKeys = [
            'id', 'name', 'visible', 'customer', 'color', 'metaFields', 'parentTitle', 'start', 'end', 'teams'
        ];

        if ($full) {
            $expectedKeys = array_merge($expectedKeys, [
                'comment', 'budget', 'timeBudget', 'orderNumber', 'orderDate'
            ]);
        }

        $actual = array_keys($result);
        sort($actual);
        sort($expectedKeys);

        $this->assertEquals($expectedKeys, $actual, 'Project structure does not match');
    }
}
