<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\BookmarkRepository;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Widget\DashboardService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

/**
 * Regression tests for GHSA-wv7c-q6q8-8rpw.
 *
 * Every test issues the request as the victim themselves, so authorization is deliberately not
 * the subject here: the requests are legitimate for that user, they are just not intentional.
 */
#[Group('integration')]
class StateChangingGetTest extends AbstractControllerBaseTestCase
{
    /**
     * @return \Generator<array{0: string}>
     */
    public static function getFormerlyStateChangingUrls(): \Generator
    {
        yield ['/favorite/timesheet/add/1'];
        yield ['/favorite/timesheet/remove/1'];
        yield ['/dashboard/add-widget/userDurationToday'];
        yield ['/dashboard/reset/'];
        yield ['/admin/permissions/roles/1/delete/token'];
        yield ['/invoice/template/1/delete/token'];
        yield ['/invoice/document/invoice/delete/token'];
    }

    /**
     * The routes are gone, their replacements live in the API and do not answer GET.
     */
    #[DataProvider('getFormerlyStateChangingUrls')]
    public function testFormerRouteIsGone(string $url): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->request($client, $url);

        self::assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode(), $url . ' must not be routable any more');
    }

    public function testFavoritesAreNotChangedByPlainGet(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(1);
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $timesheets = $this->importFixture($fixture);
        $id = $timesheets[0]->getId();
        self::assertNotNull($id);

        // the API route exists, but only for POST and DELETE
        $this->requestPure($client, '/api/favorites/timesheets/' . $id);
        self::assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());

        /** @var BookmarkRepository $repository */
        $repository = $this->getPrivateService(BookmarkRepository::class);
        $this->getEntityManager()->clear();
        $bookmark = $repository->findBookmark($this->getUserByRole(User::ROLE_USER), 'favorite', 'recent');

        self::assertNull($bookmark, 'a cross site GET must not create a favorite');
    }

    public function testDashboardIsNotChangedByPlainGet(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->requestPure($client, '/api/dashboard/widgets/userDurationToday');
        self::assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());

        $this->requestPure($client, '/api/dashboard/widgets');
        self::assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());

        /** @var BookmarkRepository $repository */
        $repository = $this->getPrivateService(BookmarkRepository::class);
        $this->getEntityManager()->clear();
        $bookmark = $repository->findBookmark($this->getUserByRole(User::ROLE_USER), DashboardService::BOOKMARK_TYPE, DashboardService::BOOKMARK_NAME);

        self::assertNull($bookmark, 'a cross site GET must not write the dashboard layout');
    }
}
