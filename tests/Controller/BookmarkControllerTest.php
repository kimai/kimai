<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Controller\BookmarkController;
use App\DataFixtures\UserFixtures;
use App\Entity\Bookmark;
use App\Entity\User;
use App\Utils\ProfileManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

#[Group('integration')]
class BookmarkControllerTest extends AbstractControllerBaseTestCase
{
    /**
     * @return iterable<array{0: string}>
     */
    public static function getUrls(): iterable
    {
        yield ['/bookmark/datatable/profile'];
        yield ['/bookmark/datatable/save'];
        yield ['/bookmark/datatable/delete'];
    }

    #[DataProvider('getUrls')]
    public function testIsSecure(string $url): void
    {
        $this->assertUrlIsSecured($url, 'POST');
    }

    #[DataProvider('getUrls')]
    public function testInvalidCsrfToken(string $url): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, $url, 'POST', [
            BookmarkController::PARAM_TOKEN_NAME => 'foo',
            BookmarkController::PARAM_DATATABLE => 'timesheet',
            BookmarkController::PARAM_PROFILE => ProfileManager::PROFILE_DESKTOP,
        ]);
        $this->assertAccessDenied($client);
    }

    #[DataProvider('getUrls')]
    public function testMissingParameters(string $url): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, $url, 'POST');
        $this->assertRouteNotFound($client);
    }

    public function testDatatableProfile(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        [$token, $name] = $this->getDatatableForm($client);

        $this->request($client, '/bookmark/datatable/profile', 'POST', [
            BookmarkController::PARAM_TOKEN_NAME => $token,
            BookmarkController::PARAM_PROFILE => ProfileManager::PROFILE_MOBILE,
        ]);
        $this->assertSuccessResponse($client);

        // the session profile was switched, so the next page renders the mobile profile as active
        $this->request($client, '/timesheet/');
        $this->assertSuccessResponse($client);
        $checked = $client->getCrawler()->filter('form[name="' . $name . '_visibility"] input[name="' . BookmarkController::PARAM_PROFILE . '"][checked]');
        self::assertCount(1, $checked);
        self::assertEquals(ProfileManager::PROFILE_MOBILE, $checked->attr('value'));
    }

    public function testDatatableProfileWithInvalidProfile(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        [$token] = $this->getDatatableForm($client);

        $this->request($client, '/bookmark/datatable/profile', 'POST', [
            BookmarkController::PARAM_TOKEN_NAME => $token,
            BookmarkController::PARAM_PROFILE => 'foo',
        ]);
        $this->assertRouteNotFound($client);
    }

    public function testDatatableSaveAndDelete(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        [$token, $name] = $this->getDatatableForm($client);

        $this->request($client, '/bookmark/datatable/save', 'POST', [
            BookmarkController::PARAM_TOKEN_NAME => $token,
            BookmarkController::PARAM_DATATABLE => $name,
            BookmarkController::PARAM_PROFILE => ProfileManager::PROFILE_DESKTOP,
            'date' => 'on',
            'description' => 'on',
            'ignored' => 'off',
        ]);
        $this->assertSuccessResponse($client);

        $bookmark = $this->findBookmark($name);
        self::assertNotNull($bookmark);
        self::assertEquals(['date' => true, 'description' => true], $bookmark->getContent());

        // the token is refreshed after each successful request
        [$token] = $this->getDatatableForm($client);

        $this->request($client, '/bookmark/datatable/delete', 'POST', [
            BookmarkController::PARAM_TOKEN_NAME => $token,
            BookmarkController::PARAM_DATATABLE => $name,
            BookmarkController::PARAM_PROFILE => ProfileManager::PROFILE_DESKTOP,
        ]);
        $this->assertSuccessResponse($client);

        self::assertNull($this->findBookmark($name));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function getDatatableForm(HttpKernelBrowser $client): array
    {
        $this->request($client, '/timesheet/');
        $this->assertSuccessResponse($client);

        $crawler = $client->getCrawler();
        $token = $crawler->filter('input[name="' . BookmarkController::PARAM_TOKEN_NAME . '"]')->attr('value');
        $name = $crawler->filter('input[name="' . BookmarkController::PARAM_DATATABLE . '"]')->attr('value');
        self::assertIsString($token);
        self::assertNotEmpty($token);
        self::assertIsString($name);
        self::assertNotEmpty($name);

        return [$token, $name];
    }

    private function findBookmark(string $name): ?Bookmark
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);

        return $em->getRepository(Bookmark::class)->findOneBy([
            'user' => $user->getId(),
            'type' => Bookmark::COLUMN_VISIBILITY,
            'name' => $name,
        ]);
    }
}
