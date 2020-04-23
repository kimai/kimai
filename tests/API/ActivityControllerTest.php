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
use App\Entity\ActivityRate;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\ActivityRateRepository;
use App\Repository\ActivityRepository;
use App\Tests\Mocks\ActivityTestMetaFieldSubscriberMock;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * @group integration
 */
class ActivityControllerTest extends APIControllerBaseTest
{
    use RateControllerTestTrait;

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

    protected function loadActivityTestData(HttpKernelBrowser $client)
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

        $activity = (new Activity())->setName('third one')->setComment('3')->setProject($project);
        $em->persist($activity);

        $activity = (new Activity())->setName('fourth one')->setComment('4')->setProject($project2)->setVisible(false);
        $em->persist($activity);

        $activity = (new Activity())->setName('fifth one')->setComment('5')->setProject($project2);
        $em->persist($activity);

        $activity = (new Activity())->setName('sixth one')->setComment('6')->setVisible(false);
        $em->persist($activity);

        $em->flush();
    }

    /**
     * @dataProvider getCollectionTestData
     */
    public function testGetCollection($url, $parameters, $expected)
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->loadActivityTestData($client);
        $this->assertAccessIsGranted($client, $url, 'GET', $parameters);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(\count($expected), \count($result));
        for ($i = 0; $i < \count($result); $i++) {
            $activity = $result[$i];
            $hasProject = $expected[$i][0];
            $this->assertStructure($activity, false);
            if ($hasProject) {
                $this->assertEquals($expected[$i][1], $activity['project']);
            }
        }
    }

    public function getCollectionTestData()
    {
        yield ['/api/activities', [], [[false], [true, 2], [true, 2], [null], [true, 1]]];
        //yield ['/api/activities', [], [[false], [false], [true, 2], [true, 1], [true, 2]]];
        yield ['/api/activities', ['globals' => 'true'], [[false], [false]]];
        yield ['/api/activities', ['globals' => 'true', 'visible' => 3], [[false], [false], [false]]];
        yield ['/api/activities', ['globals' => 'true', 'visible' => '2'], [[false]]];
        yield ['/api/activities', ['globals' => 'true', 'visible' => 1], [[false], [false]]];
        yield ['/api/activities', ['project' => '1'], [[false], [false], [true, 1]]];
        yield ['/api/activities', ['project' => '2', 'projects' => '2', 'visible' => 1], [[false], [true, 2], [true, 2], [false]]];
        yield ['/api/activities', ['project' => '2', 'projects' => '2,2', 'visible' => '3'], [[false], [true, 2], [true, 2], [true, 2], [false], [false]]];
        yield ['/api/activities', ['projects' => '2,2', 'visible' => 2], [[true, 2], [false]]];
        yield ['/api/activities', ['projects' => '2', 'visible' => 2], [[true, 2], [false]]];
    }

    public function testGetCollectionWithQuery()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->loadActivityTestData($client);

        $query = ['order' => 'ASC', 'orderBy' => 'project'];
        $this->assertAccessIsGranted($client, '/api/activities', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(5, \count($result));
        $this->assertStructure($result[0], false);
        $this->assertEquals(1, $result[4]['project']);
        $this->assertEquals(2, $result[3]['project']);
        $this->assertEquals(2, $result[2]['project']);
    }

    public function testGetEntity()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/activities/1');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertStructure($result, true);
    }

    public function testNotFound()
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/activities/2');
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
        $this->assertStructure($result);
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
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('User cannot create activities', $json['message']);
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
            'project' => 1,
            'visible' => true,
            'budget' => '999',
            'timeBudget' => '7200',
        ];
        $this->request($client, '/api/activities/1', 'PATCH', [], json_encode($data));
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
            'project' => 1,
            'visible' => true
        ];
        $this->request($client, '/api/activities/1', 'PATCH', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('User cannot update activity', $json['message']);
    }

    public function testPatchActionWithUnknownActivity()
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_USER, '/api/activities/255', []);
    }

    public function testInvalidPatchAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'project' => 255,
            'visible' => true
        ];
        $this->request($client, '/api/activities/1', 'PATCH', [], json_encode($data));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['project']);
    }

    public function testMetaActionThrowsNotFound()
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_ADMIN, '/api/activities/42/meta', []);
    }

    public function testMetaActionThrowsExceptionOnMissingName()
    {
        return $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/activities/1/meta', ['value' => 'X'], [
            'code' => 400,
            'message' => 'Parameter "name" of value "NULL" violated a constraint "This value should not be null."'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingValue()
    {
        return $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/activities/1/meta', ['name' => 'X'], [
            'code' => 400,
            'message' => 'Parameter "value" of value "NULL" violated a constraint "This value should not be null."'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingMetafield()
    {
        return $this->assertExceptionForPatchAction(User::ROLE_ADMIN, '/api/activities/1/meta', ['name' => 'X', 'value' => 'Y'], [
            'code' => 500,
            'message' => 'Unknown meta-field requested'
        ]);
    }

    public function testMetaAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        static::$kernel->getContainer()->get('event_dispatcher')->addSubscriber(new ActivityTestMetaFieldSubscriberMock());

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

    protected function assertStructure(array $result, $full = true)
    {
        $expectedKeys = [
            'id', 'name', 'visible', 'project', 'color', 'metaFields', 'parentTitle'
        ];

        if ($full) {
            $expectedKeys = array_merge($expectedKeys, [
                'comment', 'budget', 'timeBudget'
            ]);
        }

        $actual = array_keys($result);
        sort($actual);
        sort($expectedKeys);

        $this->assertEquals($expectedKeys, $actual, 'Activity structure does not match');
    }
}
