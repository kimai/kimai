<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\EventSubscriber\LocaleNotFoundSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Verifies that URLs with a not translated locale (e.g. from old bookmarks or user profiles) are redirected instead of 404.
 */
#[Group('integration')]
#[CoversClass(LocaleNotFoundSubscriber::class)]
class LocaleNotFoundTest extends AbstractControllerBaseTestCase
{
    public function testRedirectsAnonymousUser(): void
    {
        $client = self::createClient();
        $this->requestPure($client, '/de_DE/login');

        self::assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertIsRedirect($client, '/de/login', false);

        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());
    }

    public function testRedirectsAuthenticatedUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->requestPure($client, '/de_DE/timesheet/?page=1');

        self::assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertIsRedirect($client, '/de/timesheet/?page=1', false);

        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());
    }

    public function testUnknownLocaleIsStillNotFound(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->requestPure($client, '/xx_YY/timesheet/');

        self::assertEquals(404, $client->getResponse()->getStatusCode());
    }
}
