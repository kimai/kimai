<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\User;
use App\Tests\DataFixtures\TagFixtures;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * @group integration
 */
class TagControllerTest extends APIControllerBaseTest
{
    protected function importTagFixtures(HttpKernelBrowser $client): void
    {
        $tagList = ['Test', 'Administration', 'Support', '#2018-001', '#2018-002', '#2018-003', 'Development',
            'Marketing', 'First Level Support', 'Bug Fixing'];

        $fixture = new TagFixtures();
        $fixture->setTagArray($tagList);
        $this->importFixture($fixture);
    }

    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/api/tags');
    }

    public function testGetCollection()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importTagFixtures($client);
        $this->assertAccessIsGranted($client, '/api/tags');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(10, \count($result));
        $this->assertEquals('Test', $result[9]);
    }

    public function testEmptyCollection()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importTagFixtures($client);
        $query = ['name' => 'nothing'];
        $this->assertAccessIsGranted($client, '/api/tags', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
        $this->assertEquals(0, \count($result));
    }

    public function testPostAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->importTagFixtures($client);
        $data = [
            'name' => 'foo',
        ];
        $this->request($client, '/api/tags', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertStructure($result);
        $this->assertNotEmpty($result['id']);
    }

    public function testPostActionWithInvalidUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importTagFixtures($client);
        $data = [
            'name' => 'foo',
        ];
        $this->request($client, '/api/tags', 'POST', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('User cannot create tags', $json['message']);
    }

    public function testPartOfEntries()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importTagFixtures($client);
        $query = ['name' => 'in'];
        $this->assertAccessIsGranted($client, '/api/tags', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(3, \count($result));

        $this->assertEquals('Administration', $result[0]);
        $this->assertEquals('Bug Fixing', $result[1]);
        $this->assertEquals('Marketing', $result[2]);
    }

    public function testDeleteAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->importTagFixtures($client);

        $this->request($client, '/api/tags/1', 'DELETE');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        $this->assertEmpty($client->getResponse()->getContent());

        $this->assertAccessIsGranted($client, '/api/tags');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(9, \count($result));
    }

    public function testDeleteActionWithUnknownTimesheet()
    {
        $this->assertEntityNotFoundForDelete(User::ROLE_ADMIN, '/api/tags/255');
    }

    protected function assertStructure(array $result, $full = true)
    {
        $expectedKeys = [
            'id', 'name', 'color', 'timesheets'
        ];

        if ($full) {
            $expectedKeys = array_merge($expectedKeys, []);
        }

        $actual = array_keys($result);
        sort($actual);
        sort($expectedKeys);

        $this->assertEquals($expectedKeys, $actual, 'Tag structure does not match');
    }
}
