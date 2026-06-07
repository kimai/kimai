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
use App\Timesheet\FavoriteRecordService;
use PHPUnit\Framework\Attributes\Group;

#[Group('integration')]
class FavoriteControllerTest extends AbstractControllerBaseTestCase
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/favorite/timesheet/');
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $start = new \DateTime('first day of this month');

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(25);
        $fixture->setAmountRunning(2);
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $fixture->setStartDate($start);
        $this->importFixture($fixture);

        $this->request($client, '/favorite/timesheet/');
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertStringContainsString('<a class="api-link text-decoration-none text-body d-block" href="#', $content);
        self::assertStringContainsString('data-href="/api/timesheets/', $content);
        self::assertStringContainsString('data-event="kimai.timesheetStart kimai.timesheetUpdate" data-method="PATCH" data-msg-error="timesheet', $content);
    }

    /**
     * Regression test for the security issue in FavoriteController::add():
     * an unprivileged user must NOT be able to add a favorite for a timesheet
     * owned by another user, even if they know a valid timesheet ID.
     */
    public function testAddFavoriteForOtherUsersTimesheetIsDenied(): void
    {
        // attacker is a plain user (ROLE_USER), not the timesheet owner
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $victim = $this->getUserByRole(User::ROLE_TEAMLEAD);
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(1);
        $fixture->setUser($victim);
        $timesheets = $this->importFixture($fixture);
        $timesheetId = $timesheets[0]->getId();
        self::assertNotNull($timesheetId);

        $this->request($client, '/favorite/timesheet/add/' . $timesheetId);

        $this->assertAccessDenied($client);

        // the victim's bookmark must not have been touched
        /** @var BookmarkRepository $bookmarkRepository */
        $bookmarkRepository = $this->getPrivateService(BookmarkRepository::class);
        $this->getEntityManager()->clear();
        $bookmark = $bookmarkRepository->findBookmark($this->getUserByRole(User::ROLE_TEAMLEAD), 'favorite', 'recent');
        if ($bookmark !== null) {
            self::assertNotContains($timesheetId, $bookmark->getContent(), 'attacker must not write to the victim\'s bookmark');
        }
    }

    /**
     * Regression test for the security issue in FavoriteController::remove():
     * an unprivileged user must NOT be able to remove a favorite from another
     * user's bookmark, even if they know a valid timesheet ID.
     */
    public function testRemoveFavoriteForOtherUsersTimesheetIsDenied(): void
    {
        // attacker is a plain user (ROLE_USER), not the timesheet owner
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $victim = $this->getUserByRole(User::ROLE_TEAMLEAD);
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(1);
        $fixture->setUser($victim);
        $timesheets = $this->importFixture($fixture);
        $timesheet = $timesheets[0];
        $timesheetId = $timesheet->getId();
        self::assertNotNull($timesheetId);

        // legitimately seed the victim's own favorites
        /** @var FavoriteRecordService $favoriteRecordService */
        $favoriteRecordService = $this->getPrivateService(FavoriteRecordService::class);
        $favoriteRecordService->addFavorite($timesheet);

        /** @var BookmarkRepository $bookmarkRepository */
        $bookmarkRepository = $this->getPrivateService(BookmarkRepository::class);
        $this->getEntityManager()->clear();
        $bookmark = $bookmarkRepository->findBookmark($this->getUserByRole(User::ROLE_TEAMLEAD), 'favorite', 'recent');
        self::assertNotNull($bookmark);
        self::assertContains($timesheetId, $bookmark->getContent(), 'precondition: favorite exists for the victim');

        // attacker (ROLE_USER) attempts to remove the favorite from the victim's bookmark
        $this->request($client, '/favorite/timesheet/remove/' . $timesheetId);

        $this->assertAccessDenied($client);

        // the victim's favorite must still be there
        /** @var BookmarkRepository $bookmarkRepository */
        $bookmarkRepository = $this->getPrivateService(BookmarkRepository::class);
        $this->getEntityManager()->clear();
        $bookmark = $bookmarkRepository->findBookmark($this->getUserByRole(User::ROLE_TEAMLEAD), 'favorite', 'recent');
        self::assertNotNull($bookmark);
        self::assertContains($timesheetId, $bookmark->getContent(), 'attacker must not remove the victim\'s favorite');
    }

    /**
     * Ensures the added `#[IsGranted('view', 'timesheet')]` voter does not
     * break the legitimate use case: a user managing favorites for their own
     * timesheet.
     */
    public function testAddAndRemoveFavoriteForOwnTimesheetIsAllowed(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $owner = $this->getUserByRole(User::ROLE_USER);
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(1);
        $fixture->setUser($owner);
        $timesheets = $this->importFixture($fixture);
        $timesheetId = $timesheets[0]->getId();
        self::assertNotNull($timesheetId);

        $this->request($client, '/favorite/timesheet/add/' . $timesheetId);
        self::assertTrue($client->getResponse()->isRedirect());

        /** @var BookmarkRepository $bookmarkRepository */
        $bookmarkRepository = $this->getPrivateService(BookmarkRepository::class);
        $this->getEntityManager()->clear();
        $bookmark = $bookmarkRepository->findBookmark($this->getUserByRole(User::ROLE_USER), 'favorite', 'recent');
        self::assertNotNull($bookmark);
        self::assertContains($timesheetId, $bookmark->getContent());

        $this->request($client, '/favorite/timesheet/remove/' . $timesheetId);
        self::assertTrue($client->getResponse()->isRedirect());

        /** @var BookmarkRepository $bookmarkRepository */
        $bookmarkRepository = $this->getPrivateService(BookmarkRepository::class);
        $this->getEntityManager()->clear();
        $bookmark = $bookmarkRepository->findBookmark($this->getUserByRole(User::ROLE_USER), 'favorite', 'recent');
        self::assertNotNull($bookmark);
        self::assertNotContains($timesheetId, $bookmark->getContent());
    }
}
