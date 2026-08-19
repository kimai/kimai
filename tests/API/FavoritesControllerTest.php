<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Repository\BookmarkRepository;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Timesheet\FavoriteRecordService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * @group integration
 */
class FavoritesControllerTest extends APIControllerBaseTestCase
{
    /**
     * @return array<int>
     */
    private function getFavorites(string $role = User::ROLE_USER): array
    {
        /** @var BookmarkRepository $repository */
        $repository = $this->getPrivateService(BookmarkRepository::class);
        $this->getEntityManager()->clear();
        $bookmark = $repository->findBookmark($this->getUserByRole($role), 'favorite', 'recent');

        return $bookmark === null ? [] : $bookmark->getContent();
    }

    private function createTimesheet(string $role): int
    {
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(1);
        $fixture->setUser($this->getUserByRole($role));
        $timesheets = $this->importFixture($fixture);

        $id = $timesheets[0]->getId();
        self::assertNotNull($id);

        return $id;
    }

    /**
     * @return array<mixed>
     */
    private function getJsonRows(HttpKernelBrowser $client): array
    {
        self::assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);

        $result = json_decode($content, true);
        self::assertIsArray($result);

        return $result;
    }

    /**
     * @return array<mixed>
     */
    private function getJsonRow(HttpKernelBrowser $client, int $index = 0): array
    {
        $rows = $this->getJsonRows($client);
        self::assertArrayHasKey($index, $rows);
        self::assertIsArray($rows[$index]);

        return $rows[$index];
    }

    public function testPostIsSecure(): void
    {
        $this->assertRequestIsSecured(self::createClient(), '/api/favorites/timesheets/1', 'POST');
    }

    public function testDeleteIsSecure(): void
    {
        $this->assertRequestIsSecured(self::createClient(), '/api/favorites/timesheets/1', 'DELETE');
    }

    public function testAddFavorite(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $id = $this->createTimesheet(User::ROLE_USER);

        self::assertNotContains($id, $this->getFavorites());

        $this->request($client, '/api/favorites/timesheets/' . $id, 'POST');

        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        self::assertContains($id, $this->getFavorites());
    }

    /**
     * Adding a favorite twice is not an error, the list simply stays as it is.
     */
    public function testAddFavoriteTwice(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $id = $this->createTimesheet(User::ROLE_USER);

        $this->request($client, '/api/favorites/timesheets/' . $id, 'POST');
        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

        $this->request($client, '/api/favorites/timesheets/' . $id, 'POST');
        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

        self::assertEquals([$id], $this->getFavorites());
    }

    public function testRemoveFavorite(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $id = $this->createTimesheet(User::ROLE_USER);

        /** @var FavoriteRecordService $favoriteRecordService */
        $favoriteRecordService = $this->getPrivateService(FavoriteRecordService::class);
        $timesheet = $this->getEntityManager()->getRepository(Timesheet::class)->find($id);
        self::assertInstanceOf(Timesheet::class, $timesheet);
        $favoriteRecordService->addFavorite($timesheet);

        self::assertContains($id, $this->getFavorites());

        $this->request($client, '/api/favorites/timesheets/' . $id, 'DELETE');

        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        self::assertNotContains($id, $this->getFavorites());
    }

    /**
     * Removing a record which is not a favorite is not an error either.
     */
    public function testRemoveUnknownFavorite(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $id = $this->createTimesheet(User::ROLE_USER);

        $this->request($client, '/api/favorites/timesheets/' . $id, 'DELETE');

        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        self::assertEquals([], $this->getFavorites());
    }

    public function testAddFavoriteForForeignTimesheetIsDenied(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $id = $this->createTimesheet(User::ROLE_TEAMLEAD);

        $this->request($client, '/api/favorites/timesheets/' . $id, 'POST');

        $this->assertApiResponseAccessDenied($client->getResponse());
        self::assertEquals([], $this->getFavorites(User::ROLE_TEAMLEAD));
    }

    public function testRemoveFavoriteForForeignTimesheetIsDenied(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $id = $this->createTimesheet(User::ROLE_TEAMLEAD);

        /** @var FavoriteRecordService $favoriteRecordService */
        $favoriteRecordService = $this->getPrivateService(FavoriteRecordService::class);
        $timesheet = $this->getEntityManager()->getRepository(Timesheet::class)->find($id);
        self::assertInstanceOf(Timesheet::class, $timesheet);
        $favoriteRecordService->addFavorite($timesheet);

        self::assertContains($id, $this->getFavorites(User::ROLE_TEAMLEAD));

        $this->request($client, '/api/favorites/timesheets/' . $id, 'DELETE');

        $this->assertApiResponseAccessDenied($client->getResponse());
        self::assertContains($id, $this->getFavorites(User::ROLE_TEAMLEAD), 'the favorite of another user must not be removed');
    }

    public function testNotFound(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertNotFoundForDelete($client, '/api/favorites/timesheets/' . PHP_INT_MAX);
    }

    /**
     * The predecessor of this endpoint changed state on a GET request, see GHSA-wv7c-q6q8-8rpw.
     */
    public function testGetIsNotAllowed(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $id = $this->createTimesheet(User::ROLE_USER);

        $this->request($client, '/api/favorites/timesheets/' . $id, 'GET');

        self::assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
        self::assertEquals([], $this->getFavorites());
    }

    public function testGetIsSecure(): void
    {
        $this->assertRequestIsSecured(self::createClient(), '/api/favorites/timesheets', 'GET');
    }

    public function testGetWithoutFavorites(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/api/favorites/timesheets', 'GET');

        self::assertEquals([], $this->getJsonRows($client));
    }

    /**
     * The underlying service fills the list up with recent records to offer them as templates.
     * Those are not favorites and may not show up here.
     */
    public function testGetReturnsOnlyFavorites(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(5);
        $fixture->setUser($this->getUserByRole(User::ROLE_ADMIN));
        $timesheets = $this->importFixture($fixture);

        $favorite = $timesheets[0];
        $favoriteId = $favorite->getId();
        self::assertNotNull($favoriteId);

        /** @var FavoriteRecordService $favoriteRecordService */
        $favoriteRecordService = $this->getPrivateService(FavoriteRecordService::class);
        $favoriteRecordService->addFavorite($favorite);

        $this->request($client, '/api/favorites/timesheets', 'GET');

        self::assertCount(1, $this->getJsonRows($client), 'only the favorite may be returned, not the recent records');

        $row = $this->getJsonRow($client);
        self::assertEquals($favoriteId, $row['id']);
        self::assertApiResponseTypeStructure('TimesheetCollectionFull', $row);
    }

    public function testGetHidesRatesWithoutPermission(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(1);
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $timesheets = $this->importFixture($fixture);

        /** @var FavoriteRecordService $favoriteRecordService */
        $favoriteRecordService = $this->getPrivateService(FavoriteRecordService::class);
        $favoriteRecordService->addFavorite($timesheets[0]);

        $this->request($client, '/api/favorites/timesheets', 'GET');

        self::assertCount(1, $this->getJsonRows($client));
        $row = $this->getJsonRow($client);

        foreach (['rate', 'internalRate', 'fixedRate', 'hourlyRate'] as $field) {
            self::assertArrayNotHasKey($field, $row, 'the rate field "' . $field . '" must not be exposed');
        }
    }
}
