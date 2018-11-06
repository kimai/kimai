<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\Query\VisibilityQuery;
use Symfony\Bundle\FrameworkBundle\Client;

/**
 * @coversDefaultClass \App\API\ActivityController
 * @group integration
 */
class ActivityControllerTest extends APIControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/api/activities');
    }

    protected function loadActivityTestData(Client $client)
    {
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $project = $em->getRepository(Project::class)->find(1);

        $project2 = new Project();
        $project2->setName('Activity Test');
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

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);
        $this->assertEquals(count($expected), count($result));
        for ($i = 0; $i < count($result); $i++) {
            $activity = $result[$i];
            $hasProject = $expected[$i][0];
            $this->assertStructure($activity, $hasProject);
            if ($hasProject) {
                $this->assertEquals($expected[$i][0], $activity['project_id']);
            }
        }
    }

    public function getCollectionTestData()
    {
        yield ['/api/activities', [], [[false], [false], [true, 2], [true, 1], [true, 2]]];
        yield ['/api/activities', ['globals' => 'true'], [[false], [false]]];
        yield ['/api/activities', ['globals' => 'true', 'visible' => VisibilityQuery::SHOW_BOTH], [[false], [false], [false]]];
        yield ['/api/activities', ['globals' => 'true', 'visible' => VisibilityQuery::SHOW_HIDDEN], [[false]]];
        yield ['/api/activities', ['globals' => 'true', 'visible' => VisibilityQuery::SHOW_VISIBLE], [[false], [false]]];
        yield ['/api/activities', ['project' => '1'], [[false], [false], [true, 1]]];
        yield ['/api/activities', ['project' => '2', 'visible' => VisibilityQuery::SHOW_VISIBLE], [[false], [false], [true, 2], [true, 2]]];
        yield ['/api/activities', ['project' => '2', 'visible' => VisibilityQuery::SHOW_BOTH], [[false], [false], [false], [true, 2], [true, 2], [true, 2]]];
        yield ['/api/activities', ['project' => '2', 'visible' => VisibilityQuery::SHOW_HIDDEN], [[false], [true, 2]]];
    }

    public function testGetEntity()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/activities/1');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertInternalType('array', $result);
        $this->assertStructure($result, false);
    }

    public function testNotFound()
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/activities/2');
    }

    protected function assertStructure(array $result, $project = true)
    {
        $expectedKeys = [
            'id', 'name', 'comment', 'visible'
        ];

        if ($project) {
            $expectedKeys[] = 'project_id';
        }

        $actual = array_keys($result);

        $this->assertEquals(count($expectedKeys), count($actual), 'Activity entity has different amount of keys: ' . $result['id']);
        $this->assertEquals($expectedKeys, $actual, 'Activity structure does not match');
    }
}
