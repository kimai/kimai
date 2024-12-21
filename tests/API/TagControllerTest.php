<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\Tag;
use App\Entity\User;
use App\Tests\DataFixtures\TagFixtures;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group integration
 */
class TagControllerTest extends APIControllerBaseTestCase
{
    /**
     * @return Tag[]
     */
    protected function importTagFixtures(): array
    {
        $tagList = ['Test', 'Administration', 'Support', '#2018-001', '#2018-002', '#2018-003', 'Development',
            'Marketing', 'First Level Support', 'Bug Fixing'];

        $fixture = new TagFixtures();
        $fixture->setTagArray($tagList);

        return $this->importFixture($fixture);
    }

    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/tags');
    }

    public function testGetCollection(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importTagFixtures();
        $this->assertAccessIsGranted($client, '/api/tags');

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(10, \count($result));
        self::assertEquals('Test', $result[9]);
    }

    public function testFindCollection(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importTagFixtures();
        $this->assertAccessIsGranted($client, '/api/tags/find', 'GET', ['name' => '2018']);
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(3, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('TagEntity', $result[0]);
    }

    public function testEmptyCollection(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importTagFixtures();
        $query = ['name' => 'nothing'];
        $this->assertAccessIsGranted($client, '/api/tags', 'GET', $query);

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertEmpty($result);
        self::assertEquals(0, \count($result));
    }

    public function testPostAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->importTagFixtures();
        $data = [
            'name' => 'foo',
            'color' => '#00ff00'
        ];
        $this->request($client, '/api/tags', 'POST', [], json_encode($data));
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TagEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertEquals('#00ff00', $result['color']);
    }

    public function testPostActionWithValidationErrors(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->importTagFixtures();
        $data = [
            'name' => '1',
            'color' => '11231231231',
        ];
        $this->request($client, '/api/tags', 'POST', [], json_encode($data));
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['name', 'color']);
    }

    public function testPostActionAsRegularUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importTagFixtures();
        $data = [
            'name' => 'foo',
        ];
        $this->request($client, '/api/tags', 'POST', [], json_encode($data));
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TagEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertEquals('foo', $result['name']);
    }

    public function testPartOfEntries(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importTagFixtures();
        $query = ['name' => 'in'];
        $this->assertAccessIsGranted($client, '/api/tags', 'GET', $query);

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(3, \count($result));

        self::assertEquals('Administration', $result[0]);
        self::assertEquals('Bug Fixing', $result[1]);
        self::assertEquals('Marketing', $result[2]);
    }

    public function testDeleteAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $tags = $this->importTagFixtures();
        $id = $tags[0]->getId();

        $this->request($client, '/api/tags/' . $id, 'DELETE');
        self::assertTrue($client->getResponse()->isSuccessful());
        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        self::assertEmpty($client->getResponse()->getContent());

        $this->assertAccessIsGranted($client, '/api/tags');

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        $result = json_decode($content, true);

        self::assertEquals(9, \count($result));
    }

    public function testDeleteActionWithUnknownTimesheet(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertNotFoundForDelete($client, '/api/tags/' . PHP_INT_MAX);
    }
}
