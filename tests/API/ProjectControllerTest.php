<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\Query\VisibilityQuery;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group integration
 */
class ProjectControllerTest extends APIControllerBaseTest
{
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
        $this->assertEquals(1, count($result));
        $this->assertStructure($result[0], false);
    }

    protected function loadProjectTestData(Client $client)
    {
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

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
        $this->assertEquals(count($expected), count($result), 'Found wrong amount of projects');

        for ($i = 0; $i < count($expected); $i++) {
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
        yield ['/api/projects', ['customer' => '1', 'visible' => VisibilityQuery::SHOW_VISIBLE], [[true, 1], [false, 1]]];
        yield ['/api/projects', ['customer' => '1', 'visible' => VisibilityQuery::SHOW_BOTH], [[true, 1], [false, 1], [false, 1]]];
        yield ['/api/projects', ['customer' => '1', 'visible' => VisibilityQuery::SHOW_HIDDEN], [[false, 1]]];
        yield ['/api/projects', ['customer' => '2', 'visible' => VisibilityQuery::SHOW_VISIBLE], []];
        yield ['/api/projects', ['customer' => '2', 'visible' => VisibilityQuery::SHOW_BOTH], [[false, 2], [false, 2]]];
        yield ['/api/projects', ['customer' => '2', 'visible' => VisibilityQuery::SHOW_HIDDEN], [[false, 2]]];
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
        ];
        $this->request($client, '/api/projects', 'POST', [], json_encode($data));
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
            'customer' => 1,
            'visible' => true
        ];
        $this->request($client, '/api/projects', 'POST', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('User cannot create projects', $json['message']);
    }

    public function testPatchAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'comment' => '',
            'customer' => 1,
            'visible' => true
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

    protected function assertStructure(array $result, $full = true)
    {
        $expectedKeys = [
            'id', 'name', 'visible', 'customer', 'hourlyRate', 'fixedRate', 'color', 'metaFields', 'parentTitle'
        ];

        if ($full) {
            $expectedKeys = array_merge($expectedKeys, [
                'comment', 'budget', 'timeBudget', 'orderNumber'
            ]);
        }

        $actual = array_keys($result);
        sort($actual);
        sort($expectedKeys);

        $this->assertEquals($expectedKeys, $actual, 'Project structure does not match');
    }
}
