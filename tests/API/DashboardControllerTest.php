<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\User;
use App\Repository\BookmarkRepository;
use App\Widget\DashboardService;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group integration
 */
class DashboardControllerTest extends APIControllerBaseTestCase
{
    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function getWidgets(string $role = User::ROLE_USER): ?array
    {
        /** @var BookmarkRepository $repository */
        $repository = $this->getPrivateService(BookmarkRepository::class);
        $this->getEntityManager()->clear();
        $bookmark = $repository->findBookmark($this->getUserByRole($role), DashboardService::BOOKMARK_TYPE, DashboardService::BOOKMARK_NAME);

        return $bookmark?->getContent();
    }

    /**
     * @return array<string>
     */
    private function getWidgetIds(string $role = User::ROLE_USER): array
    {
        $widgets = $this->getWidgets($role);

        if ($widgets === null) {
            return [];
        }

        $ids = [];
        foreach (array_column($widgets, 'id') as $id) {
            $ids[] = (string) $id; // @phpstan-ignore cast.string
        }

        return $ids;
    }

    public function testPostIsSecure(): void
    {
        $this->assertRequestIsSecured(self::createClient(), '/api/dashboard/widgets/userDurationToday', 'POST');
    }

    public function testDeleteIsSecure(): void
    {
        $this->assertRequestIsSecured(self::createClient(), '/api/dashboard/widgets', 'DELETE');
    }

    public function testAddWidget(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        self::assertNull($this->getWidgets(), 'precondition: the user has no custom dashboard');

        $this->request($client, '/api/dashboard/widgets/userDurationToday', 'POST');

        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        self::assertContains('userDurationToday', $this->getWidgetIds());
    }

    /**
     * Adding the same widget twice must not duplicate it.
     */
    public function testAddWidgetTwice(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->request($client, '/api/dashboard/widgets/userDurationToday', 'POST');
        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        $first = $this->getWidgetIds();

        $this->request($client, '/api/dashboard/widgets/userDurationToday', 'POST');
        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

        self::assertEquals($first, $this->getWidgetIds());
        self::assertCount(1, array_keys($first, 'userDurationToday', true));
    }

    /**
     * Regression test for GHSA-wv7c-q6q8-8rpw: the widget name was not validated at all,
     * so any string ended up in the stored dashboard configuration.
     */
    public function testAddUnknownWidgetIsNotFound(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->request($client, '/api/dashboard/widgets/ThisWidgetDoesNotExist', 'POST');

        self::assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        self::assertNull($this->getWidgets(), 'an unknown widget must not be stored');
    }

    /**
     * Widgets which are not offered in the "add widget" menu may not be added either.
     */
    public function testAddInternalWidgetIsNotFound(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->request($client, '/api/dashboard/widgets/DailyWorkingTimeChart', 'POST');

        self::assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        self::assertNull($this->getWidgets(), 'an internal widget must not be stored');
    }

    /**
     * A widget the user is not allowed to see may not be added through a crafted request.
     * AmountToday requires "view_all_data".
     */
    public function testAddWidgetWithoutPermissionIsNotFound(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->request($client, '/api/dashboard/widgets/AmountToday', 'POST');

        self::assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        self::assertNull($this->getWidgets(), 'a widget without permission must not be stored');
    }

    public function testAddWidgetWithPermission(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $this->request($client, '/api/dashboard/widgets/AmountToday', 'POST');

        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        self::assertContains('AmountToday', $this->getWidgetIds(User::ROLE_SUPER_ADMIN));
    }

    public function testResetWidgets(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->request($client, '/api/dashboard/widgets/userDurationToday', 'POST');
        self::assertNotNull($this->getWidgets(), 'precondition: a custom dashboard exists');

        $this->request($client, '/api/dashboard/widgets', 'DELETE');

        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        self::assertNull($this->getWidgets());
    }

    /**
     * Resetting a dashboard which was never customized is not an error.
     */
    public function testResetWithoutCustomDashboard(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->request($client, '/api/dashboard/widgets', 'DELETE');

        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        self::assertNull($this->getWidgets());
    }

    /**
     * Both endpoints used to change state on a GET request, see GHSA-wv7c-q6q8-8rpw.
     */
    public function testGetIsNotAllowed(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->request($client, '/api/dashboard/widgets/userDurationToday', 'GET');
        self::assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());

        $this->request($client, '/api/dashboard/widgets', 'GET');
        self::assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());

        self::assertNull($this->getWidgets(), 'a GET must not change the dashboard');
    }
}
