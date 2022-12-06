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

/**
 * @group integration
 */
class ActivityControllerTest extends APIControllerBaseTest
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
            return sprintf('/api/activities/%s/rates/%s', $id, $rateId);
        }

        return sprintf('/api/activities/%s/rates', $id);
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

    public function testIsSecure()
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
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(\count($expected), \count($result));
        for ($i = 0; $i < \count($result); $i++) {
            $activity = $result[$i];
            $hasProject = $expected[$i][0];
            self::assertApiResponseTypeStructure('ActivityCollection', $activity);
            if ($hasProject && $projectId !== null) {
                $this->assertEquals($projectId, $activity['project']);
            }
        }
    }

    /**
     * @return \Generator<array<mixed>>
     */
    public function getCollectionTestData(): iterable
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

    public function testGetCollectionWithQuery()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $imports = $this->loadActivityTestData();

        $query = ['order' => 'ASC', 'orderBy' => 'project'];
        $this->assertAccessIsGranted($client, '/api/activities', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(5, \count($result));
        self::assertApiResponseTypeStructure('ActivityCollection', $result[0]);
        $this->assertEquals($imports[0]->getId(), $result[4]['project']);
        $this->assertEquals($imports[1]->getId(), $result[3]['project']);
        $this->assertEquals($imports[1]->getId(), $result[2]['project']);
    }

    public function testGetEntity()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/activities/1');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('ActivityEntity', $result);
    }

    public function testNotFound()
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/activities/' . PHP_INT_MAX, 'GET', 'App\\Entity\\Activity object not found by the @ParamConverter annotation.');
    }

    public function testPostAction()
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
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('ActivityEntity', $result);
        $this->assertNotEmpty($result['id']);
    }

    public function testPostActionWithLeastFields()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
        ];
        $this->request($client, '/api/activities', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('ActivityEntity', $result);
        $this->assertNotEmpty($result['id']);
    }

    public function testPostActionWithInvalidUser()
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

    public function testPostActionWithInvalidData()
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

    public function testPatchAction()
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
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('ActivityEntity', $result);
        $this->assertNotEmpty($result['id']);
    }

    public function testPatchActionWithNonGlobalActivity()
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
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('ActivityEntity', $result);
        $this->assertNotEmpty($result['id']);
        $this->assertEquals($imports[1]->getId(), $result['project']);
    }

    public function testPatchActionWithInvalidUser()
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

    public function testPatchActionWithUnknownActivity()
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_USER, '/api/activities/255', []);
    }

    public function testInvalidPatchAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoofoo',
            'visible' => false
        ];
        $this->request($client, '/api/activities/1', 'PATCH', [], json_encode($data));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['name']);
    }

    public function testMetaActionThrowsNotFound()
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_ADMIN, '/api/activities/42/meta', []);
    }

    public function testMetaActionThrowsExceptionOnMissingName()
    {
        $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/activities/1/meta', ['value' => 'X'], [
            'code' => 400,
            'message' => 'Bad Request'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingValue()
    {
        $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/activities/1/meta', ['name' => 'X'], [
            'code' => 400,
            'message' => 'Bad Request'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingMetafield()
    {
        $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/activities/1/meta', ['name' => 'X', 'value' => 'Y'], [
            'code' => 404,
            'message' => 'Not Found'
        ]);
    }

    public function testMetaAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        static::getContainer()->get('event_dispatcher')->addSubscriber(new ActivityTestMetaFieldSubscriberMock());

        $data = [
            'name' => 'metatestmock',
            'value' => 'another,testing,bar'
        ];
        $this->request($client, '/api/activities/1/meta', 'PATCH', [], json_encode($data));

        $this->assertTrue($client->getResponse()->isSuccessful());

        $em = $this->getEntityManager();
        /** @var Activity $activity */
        $activity = $em->getRepository(Activity::class)->find(1);
        $this->assertEquals('another,testing,bar', $activity->getMetaField('metatestmock')->getValue());
    }
}
