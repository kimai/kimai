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
use App\Entity\ActivityMeta;
use App\Entity\ActivityRate;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\RateInterface;
use App\Entity\User;
use App\Repository\ActivityRateRepository;
use App\Repository\ActivityRepository;
use App\Repository\Query\VisibilityInterface;
use App\Tests\Mocks\ActivityTestMetaFieldSubscriberMock;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group integration
 */
class ActivityControllerTest extends APIControllerBaseTestCase
{
    use RateControllerTestTrait;

    /**
     * @param ActivityRate $rate
     * @param bool $isCollection
     * @return string
     */
    protected function getRateUrlByRate(RateInterface $rate, bool $isCollection): string
    {
        if ($isCollection) {
            return $this->getRateUrl($rate->getActivity()->getId());
        }

        return $this->getRateUrl($rate->getActivity()->getId(), $rate->getId());
    }

    protected function getRateUrl($id = '1', $rateId = null): string
    {
        if (null !== $rateId) {
            return \sprintf('/api/activities/%s/rates/%s', $id, $rateId);
        }

        return \sprintf('/api/activities/%s/rates', $id);
    }

    protected function importTestRates($id): array
    {
        /** @var ActivityRateRepository $rateRepository */
        $rateRepository = $this->getEntityManager()->getRepository(ActivityRate::class);
        /** @var ActivityRepository $repository */
        $repository = $this->getEntityManager()->getRepository(Activity::class);
        /** @var Activity|null $activity */
        $activity = $repository->find($id);

        if (null === $activity) {
            $activity = new Activity();
            $activity->setName('foooo');
            $repository->saveActivity($activity);
        }

        $rate1 = new ActivityRate();
        $rate1->setActivity($activity);
        $rate1->setRate(17.45);
        $rate1->setIsFixed(false);

        $rateRepository->saveRate($rate1);

        $rate2 = new ActivityRate();
        $rate2->setActivity($activity);
        $rate2->setRate(99);
        $rate2->setInternalRate(9);
        $rate2->setIsFixed(true);
        $rate2->setUser($this->getUserByName(UserFixtures::USERNAME_USER));

        $rateRepository->saveRate($rate2);

        return [$rate1, $rate2];
    }

    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/activities');
    }

    /**
     * @return array<Project|Activity>
     * @throws \Exception
     */
    protected function loadActivityTestData(): array
    {
        $em = $this->getEntityManager();

        /** @var Project $project */
        $project = $em->getRepository(Project::class)->find(1);
        /** @var Customer $customer */
        $customer = $em->getRepository(Customer::class)->find(1);

        $project2 = new Project();
        $project2->setName('Activity Test');
        $project2->setCustomer($customer);
        $em->persist($project2);

        $activity = (new Activity())->setName('first one')->setComment('1')->setProject($project2);
        $em->persist($activity);

        $activity = (new Activity())->setName('second one')->setComment('2');
        $em->persist($activity);

        $activity1 = (new Activity())->setName('third one')->setComment('3')->setProject($project);
        $em->persist($activity1);

        $activity = (new Activity())->setName('fourth one')->setComment('4')->setProject($project2)->setVisible(false);
        $em->persist($activity);

        $activity = (new Activity())->setName('fifth one')->setComment('5')->setProject($project2);
        $meta = new ActivityMeta();
        $meta->setName('bar')->setValue('foo')->setIsVisible(false);
        $activity->setMetaField($meta);
        $meta = new ActivityMeta();
        $meta->setName('foo')->setValue('bar')->setIsVisible(true);
        $activity->setMetaField($meta);
        $em->persist($activity);

        $activity = (new Activity())->setName('sixth one')->setComment('6')->setVisible(false);
        $em->persist($activity);

        $em->flush();

        return [$project, $project2, $activity1];
    }

    /**
     * @dataProvider getCollectionTestData
     */
    public function testGetCollection($url, $project, $parameters, $expected): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $imports = $this->loadActivityTestData();

        $projectId = $project !== null ? $imports[$project]->getId() : null;
        if ($projectId !== null) {
            if (\array_key_exists('project', $parameters)) {
                $parameters['project'] = $projectId;
            }

            if (\array_key_exists('projects', $parameters)) {
                if (!\is_array($parameters['projects'])) {
                    throw new \InvalidArgumentException('projects needs to be an array');
                }
                $count = \count($parameters['projects']);
                if ($count === 2) {
                    $parameters['projects'] = [$projectId, $projectId];
                } elseif ($count === 1) {
                    $parameters['projects'] = [$projectId];
                } else {
                    throw new \InvalidArgumentException('Invalid count for projects');
                }
            }
        }

        $this->assertAccessIsGranted($client, $url, 'GET', $parameters);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(\count($expected), \count($result));
        for ($i = 0; $i < \count($result); $i++) {
            $activity = $result[$i];
            $hasProject = $expected[$i][0];
            self::assertIsArray($activity);
            self::assertApiResponseTypeStructure('ActivityCollection', $activity);
            if ($hasProject && $projectId !== null) {
                self::assertEquals($projectId, $activity['project']);
            }
        }
    }

    /**
     * @return \Generator<array<mixed>>
     */
    public static function getCollectionTestData(): iterable
    {
        yield ['/api/activities', null, [], [[false], [true, 2], [true, 2], [null], [true, 1]]];
        //yield ['/api/activities', [], [[false], [false], [true, 2], [true, 1], [true, 2]]];
        yield ['/api/activities', null, ['globals' => 'true'], [[false], [false]]];
        yield ['/api/activities', null, ['globals' => 'true', 'visible' => VisibilityInterface::SHOW_BOTH], [[false], [false], [false]]];
        yield ['/api/activities', null, ['globals' => 'true', 'visible' => VisibilityInterface::SHOW_HIDDEN], [[false]]];
        yield ['/api/activities', null, ['globals' => 'true', 'visible' => VisibilityInterface::SHOW_VISIBLE], [[false], [false]]];
        yield ['/api/activities', 0, ['project' => '1'], [[false], [false], [true, 1]]];
        yield ['/api/activities', 1, ['project' => '2', 'projects' => ['2'], 'visible' => VisibilityInterface::SHOW_VISIBLE], [[true, 2], [true, 2], [false], [false]]];
        yield ['/api/activities', 1, ['project' => '2', 'projects' => ['2', '2'], 'visible' => VisibilityInterface::SHOW_BOTH], [[true, 2], [true, 2], [true, 2], [false], [false], [false]]];
        yield ['/api/activities', 1, ['projects' => ['2', '2'], 'visible' => VisibilityInterface::SHOW_HIDDEN], [[true, 2], [false]]];
        yield ['/api/activities', 1, ['projects' => ['2'], 'visible' => VisibilityInterface::SHOW_HIDDEN], [[true, 2], [false]]];
    }

    public function testGetCollectionWithQuery(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $imports = $this->loadActivityTestData();

        $query = ['order' => 'ASC', 'orderBy' => 'project'];
        $this->assertAccessIsGranted($client, '/api/activities', 'GET', $query);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(5, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('ActivityCollection', $result[0]);
        self::assertEquals($imports[0]->getId(), $result[4]['project']);
        self::assertEquals($imports[1]->getId(), $result[3]['project']);
        self::assertEquals($imports[1]->getId(), $result[2]['project']);
    }

    public function testGetEntityIsSecure(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/api/activities/1');
    }

    public function testGetEntity(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/api/activities/1');

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('ActivityEntity', $result);
    }

    public function testNotFound(): void
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/activities/' . PHP_INT_MAX);
    }

    public function testPostAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'project' => 1,
            'visible' => true,
            'budget' => '999',
            'timeBudget' => '7200',
        ];
        $this->request($client, '/api/activities', 'POST', [], json_encode($data));
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('ActivityEntity', $result);
        self::assertNotEmpty($result['id']);
    }

    public function testPostActionWithLeastFields(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
        ];
        $this->request($client, '/api/activities', 'POST', [], json_encode($data));
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('ActivityEntity', $result);
        self::assertNotEmpty($result['id']);
    }

    public function testPostActionWithInvalidUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $data = [
            'name' => 'foo',
            'project' => 1,
            'visible' => true
        ];
        $this->request($client, '/api/activities', 'POST', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertApiResponseAccessDenied($response, 'User cannot create activities');
    }

    public function testPostActionWithInvalidData(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'project' => 100,
            'unexpected' => 'foo-bar',
            'visible' => true
        ];
        $this->request($client, '/api/activities', 'POST', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertApiCallValidationError($response, ['project'], true);
    }

    public function testPatchAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'comment' => '',
            'visible' => true,
            'budget' => '999',
            'timeBudget' => '7200',
        ];
        $this->request($client, '/api/activities/1', 'PATCH', [], json_encode($data));
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('ActivityEntity', $result);
        self::assertNotEmpty($result['id']);
    }

    public function testPatchActionWithNonGlobalActivity(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $imports = $this->loadActivityTestData();

        $data = [
            'name' => 'foo',
            'comment' => '',
            'visible' => true,
            'project' => $imports[1]->getId(),
            'budget' => '999',
            'timeBudget' => '7200',
        ];
        $this->request($client, '/api/activities/' . $imports[2]->getId(), 'PATCH', [], json_encode($data));
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('ActivityEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertEquals($imports[1]->getId(), $result['project']);
    }

    public function testPatchActionWithInvalidUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $data = [
            'name' => 'foo',
            'comment' => '',
            'project' => 1,
            'visible' => true
        ];
        $this->request($client, '/api/activities/1', 'PATCH', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertApiResponseAccessDenied($response, 'User cannot update activity');
    }

    public function testPatchActionWithUnknownActivity(): void
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_USER, '/api/activities/255', []);
    }

    public function testInvalidPatchAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoo',
            'visible' => false
        ];
        $this->request($client, '/api/activities/1', 'PATCH', [], json_encode($data));

        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['name']);
    }

    public function testMetaActionThrowsNotFound(): void
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_ADMIN, '/api/activities/42/meta', []);
    }

    public function testMetaActionThrowsExceptionOnMissingName(): void
    {
        $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/activities/1/meta', ['value' => 'X'], [
            'code' => 400,
            'message' => 'Bad Request'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingValue(): void
    {
        $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/activities/1/meta', ['name' => 'X'], [
            'code' => 400,
            'message' => 'Bad Request'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingMetafield(): void
    {
        $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/activities/1/meta', ['name' => 'X', 'value' => 'Y'], [
            'code' => 404,
            'message' => 'Not Found'
        ]);
    }

    public function testMetaAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        /** @var EventDispatcher $dispatcher */
        $dispatcher = static::getContainer()->get('event_dispatcher');
        $dispatcher->addSubscriber(new ActivityTestMetaFieldSubscriberMock());

        $data = [
            'name' => 'metatestmock',
            'value' => 'another,testing,bar'
        ];
        $this->request($client, '/api/activities/1/meta', 'PATCH', [], json_encode($data));

        self::assertTrue($client->getResponse()->isSuccessful());

        $em = $this->getEntityManager();
        /** @var Activity $activity */
        $activity = $em->getRepository(Activity::class)->find(1);
        self::assertEquals('another,testing,bar', $activity->getMetaField('metatestmock')->getValue());
    }

    // ------------------------------- [DELETE] -------------------------------

    public function testDeleteIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/activities/1', Request::METHOD_DELETE);
    }

    public function testDeleteActionWithUnknownTimesheet(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertNotFoundForDelete($client, '/api/activities/' . PHP_INT_MAX);
    }

    public function testDeleteEntityIsSecure(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/api/activities/1', Request::METHOD_DELETE);
    }

    public function testDeleteActionWithoutAuthorization(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $imports = $this->loadActivityTestData();

        $this->request($client, '/api/activities/' . $imports[2]->getId(), Request::METHOD_DELETE);

        $response = $client->getResponse();
        $this->assertApiResponseAccessDenied($response);
    }

    public function testDeleteAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $imports = $this->loadActivityTestData();
        $getUrl = '/api/activities/' . $imports[2]->getId();
        $this->assertAccessIsGranted($client, $getUrl);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('ActivityEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertIsNumeric($result['id']);
        $id = $result['id'];

        $this->request($client, '/api/activities/' . $id, Request::METHOD_DELETE);
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
