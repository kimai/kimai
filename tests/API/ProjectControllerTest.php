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
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group integration
 */
class ProjectControllerTest extends APIControllerBaseTestCase
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
            return \sprintf('/api/projects/%s/rates/%s', $id, $rateId);
        }

        return \sprintf('/api/projects/%s/rates', $id);
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

    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/projects');
    }

    public function testGetCollection(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/projects');

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(1, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('ProjectCollection', $result[0]);
    }

    /**
     * @return array{0: Project, 1: Project, 2: Project, 3: Project, 4: Project}
     */
    protected function loadProjectTestData(): array
    {
        $em = $this->getEntityManager();

        /** @var Customer $customer */
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

        $project1 = new Project();
        $project1->setName('first');
        $project1->setVisible(false);
        $project1->setCustomer($customer2);
        $em->persist($project1);

        $project2 = new Project();
        $project2->setName('second');
        $project2->setVisible(false);
        $project2->setCustomer($customer);
        $em->persist($project2);

        $project3 = new Project();
        $project3->setName('third');
        $project3->setVisible(true);
        $project3->setCustomer($customer2);
        $em->persist($project3);

        $project4 = new Project();
        $project4->setName('fourth');
        $project4->setVisible(true);
        $project4->setCustomer($customer3);
        $em->persist($project4);

        $project5 = new Project();
        $project5->setName('fifth');
        $project5->setVisible(true);
        $project5->setCustomer($customer);

        // add meta fields
        $meta = new ProjectMeta();
        $meta->setName('bar')->setValue('foo')->setIsVisible(false);
        $project5->setMetaField($meta);
        $meta = new ProjectMeta();
        $meta->setName('foo')->setValue('bar')->setIsVisible(true);
        $project5->setMetaField($meta);
        $em->persist($project5);

        // and a team
        $team = new Team('Testing project team');
        $team->addTeamlead($this->getUserByRole(User::ROLE_USER));
        $team->addCustomer($customer);
        $team->addProject($project5);
        $team->addUser($this->getUserByRole(User::ROLE_TEAMLEAD));
        $em->persist($team);

        $project = (new Project())->setName('sixth')->setVisible(false)->setCustomer($customer3);
        $em->persist($project);

        $em->flush();

        return [
            $project1,
            $project2,
            $project3,
            $project4,
            $project5,
        ];
    }

    /**
     * @dataProvider getCollectionTestData
     */
    public function testGetCollectionWithParams(string $url, ?int $project, array $parameters, array $expected): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $imports = $this->loadProjectTestData();

        $customerId = $project !== null ? $imports[$project]->getCustomer()?->getId() : null;

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

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertEquals(\count($expected), \count($result), 'Found wrong amount of projects');

        for ($i = 0; $i < \count($expected); $i++) {
            $project = $result[$i];
            self::assertIsArray($project);
            self::assertApiResponseTypeStructure('ProjectCollection', $project);
            if ($customerId !== null) {
                self::assertEquals($customerId, $project['customer']);
            }
        }
    }

    /**
     * @return \Generator<array<mixed>>
     */
    public static function getCollectionTestData(): iterable
    {
        // if you wonder why: case-sensitive ordering feels strange ... "Title" > "fifth”
        yield ['/api/projects', null, [], [[true, 1], [false, 1], [false, 3]]];
        yield ['/api/projects', 1, ['customer' => '1'], [[true, 1], [false, 1]]];
        yield ['/api/projects', 1, ['customer' => '1', 'visible' => VisibilityInterface::SHOW_VISIBLE], [[true, 1], [false, 1]]];
        yield ['/api/projects', 1, ['customer' => '1', 'visible' => VisibilityInterface::SHOW_BOTH], [[true, 1], [false, 1], [false, 1]]];
        yield ['/api/projects', 1, ['customer' => '1', 'visible' => VisibilityInterface::SHOW_HIDDEN], [[false, 1]]];
        // customer is invisible => query only returns results for VisibilityInterface::SHOW_BOTH
        yield ['/api/projects', 0, ['customer' => '2', 'visible' => VisibilityInterface::SHOW_VISIBLE], []];
        yield ['/api/projects', 0, ['customer' => '2', 'visible' => VisibilityInterface::SHOW_BOTH], [[false, 2], [false, 2]]];
        yield ['/api/projects', 0, ['customer' => '2', 'customers' => ['2'], 'visible' => VisibilityInterface::SHOW_BOTH], [[false, 2], [false, 2]]];
        yield ['/api/projects', 0, ['customers' => ['2', '2'], 'visible' => VisibilityInterface::SHOW_BOTH], [[false, 2], [false, 2]]];
        yield ['/api/projects', 0, ['customer' => '2', 'visible' => VisibilityInterface::SHOW_HIDDEN], []];
        yield ['/api/projects', 0, ['customer' => '2', 'visible' => VisibilityInterface::SHOW_HIDDEN, 'start' => '2010-12-11', 'end' => '2030-12-11'], []];
        yield ['/api/projects', 0, ['customers' => ['2'], 'visible' => VisibilityInterface::SHOW_HIDDEN, 'start' => '2010-12-11', 'end' => '2030-12-11'], []];
        yield ['/api/projects', 0, ['customers' => ['2', '2'], 'visible' => VisibilityInterface::SHOW_HIDDEN, 'start' => '2010-12-11', 'end' => '2030-12-11'], []];
    }

    public function testGetEntityIsSecure(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/api/projects/1');
    }

    public function testGetEntity(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/api/projects/1');

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('ProjectEntity', $result);
    }

    public function testGetEntityComplex(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
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

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
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

    public function testNotFound(): void
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/projects/' . PHP_INT_MAX);
    }

    public function testPostAction(): void
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
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('ProjectEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertEquals('2018-04-17', $result['orderDate']);
        self::assertEquals('2019-02-01', $result['start']);
        self::assertEquals('2020-02-08', $result['end']);
        self::assertEquals('1234567890/WXYZ/SUBPROJECT/1234/CONTRACT/EMPLOYEE1', $result['orderNumber']);
        self::assertFalse($result['globalActivities']);
        self::assertFalse($result['billable']);
        self::assertFalse($result['visible']);
    }

    public function testPostActionWithOtherFields(): void
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
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('ProjectEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertEquals('foo', $result['name']);
        self::assertTrue($result['globalActivities']);
        self::assertTrue($result['billable']);
        self::assertTrue($result['visible']);
    }

    public function testPostActionWithOtherFieldsAndFalse(): void
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
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('ProjectEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertEquals('foo', $result['name']);
        self::assertFalse($result['globalActivities']);
        self::assertFalse($result['billable']);
        self::assertFalse($result['visible']);
    }

    public function testPostActionWithOtherFields3(): void
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
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('ProjectEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertEquals('foo', $result['name']);
        self::assertTrue($result['globalActivities']);
        self::assertTrue($result['billable']);
        self::assertTrue($result['visible']);
    }

    public function testPostActionWithLeastFields(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'customer' => 1
        ];
        $this->request($client, '/api/projects', 'POST', [], json_encode($data));
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('ProjectEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertEquals('foo', $result['name']);
        self::assertFalse($result['globalActivities']);
        self::assertFalse($result['billable']);
        self::assertFalse($result['visible']);
    }

    public function testPostActionWithInvalidUser(): void
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

    public function testPostActionWithInvalidData(): void
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

    public function testPatchAction(): void
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
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('ProjectEntity', $result);
        self::assertNotEmpty($result['id']);
    }

    public function testPatchActionWithInvalidUser(): void
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

    public function testPatchActionWithUnknownActivity(): void
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_USER, '/api/projects/255', []);
    }

    public function testInvalidPatchAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'customer' => 255,
            'visible' => true
        ];
        $this->request($client, '/api/projects/1', 'PATCH', [], json_encode($data));

        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['customer']);
    }

    public function testMetaActionThrowsNotFound(): void
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_ADMIN, '/api/projects/42/meta', []);
    }

    public function testMetaActionThrowsExceptionOnMissingName(): void
    {
        $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/projects/1/meta', ['value' => 'X'], [
            'code' => 400,
            'message' => 'Bad Request'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingValue(): void
    {
        $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/projects/1/meta', ['name' => 'X'], [
            'code' => 400,
            'message' => 'Bad Request'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingMetafield(): void
    {
        $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/projects/1/meta', ['name' => 'X', 'value' => 'Y'], [
            'code' => 404,
            'message' => 'Not Found'
        ]);
    }

    public function testMetaAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        /** @var EventDispatcher $dispatcher */
        $dispatcher = static::getContainer()->get('event_dispatcher');
        $dispatcher->addSubscriber(new ProjectTestMetaFieldSubscriberMock());

        $data = [
            'name' => 'metatestmock',
            'value' => 'another,testing,bar'
        ];
        $this->request($client, '/api/projects/1/meta', 'PATCH', [], json_encode($data));

        self::assertTrue($client->getResponse()->isSuccessful());

        $em = $this->getEntityManager();
        /** @var Project $project */
        $project = $em->getRepository(Project::class)->find(1);
        self::assertEquals('another,testing,bar', $project->getMetaField('metatestmock')->getValue());
    }

    // ------------------------------- [DELETE] -------------------------------

    public function testDeleteIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/projects/1', Request::METHOD_DELETE);
    }

    public function testDeleteActionWithUnknownTimesheet(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertNotFoundForDelete($client, '/api/projects/' . PHP_INT_MAX);
    }

    public function testDeleteEntityIsSecure(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/api/projects/1', Request::METHOD_DELETE);
    }

    public function testDeleteActionWithoutAuthorization(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $imports = $this->loadProjectTestData();

        $this->request($client, '/api/projects/' . $imports[2]->getId(), Request::METHOD_DELETE);

        $response = $client->getResponse();
        $this->assertApiResponseAccessDenied($response);
    }

    public function testDeleteAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $imports = $this->loadProjectTestData();
        $getUrl = '/api/projects/' . $imports[2]->getId();
        $this->assertAccessIsGranted($client, $getUrl);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('ProjectEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertIsNumeric($result['id']);
        $id = $result['id'];

        $this->request($client, '/api/projects/' . $id, Request::METHOD_DELETE);
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
