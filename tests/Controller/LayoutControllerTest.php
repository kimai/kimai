<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * @group integration
 */
class LayoutControllerTest extends AbstractControllerBaseTestCase
{
    public function testNavigationMenus(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $user = $this->getUserByRole(User::ROLE_USER);

        $this->request($client, '/dashboard/');
        self::assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasMainHeader($client, $user);
        $this->assertHasNavigation($client);
    }

    protected function assertHasMainHeader(HttpKernelBrowser $client, User $user): void
    {
        $content = $client->getResponse()->getContent();

        self::assertStringContainsString('data-bs-toggle="dropdown" aria-label="Open personal menu"', $content);
        self::assertStringContainsString('href="/en/profile/' . $user->getUserIdentifier() . '"', $content);
        self::assertStringContainsString('href="/en/profile/' . $user->getUserIdentifier() . '/edit"', $content);
        self::assertStringContainsString('href="/en/profile/' . $user->getUserIdentifier() . '/prefs"', $content);
        self::assertStringContainsString('href="/en/logout', $content);
    }

    protected function assertHasNavigation(HttpKernelBrowser $client): void
    {
        $content = $client->getResponse()->getContent();

        self::assertStringContainsString('href="/en/dashboard/"', $content);
        self::assertStringContainsString('href="/en/timesheet/"', $content);
        self::assertStringContainsString('My times', $content);
        self::assertStringContainsString('href="/en/calendar/"', $content);
        self::assertStringContainsString('Calendar', $content);
    }

    public function testActiveEntries(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->request($client, '/dashboard/');
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();

        self::assertStringContainsString('<a title="Start time-tracking" href="/en/timesheet/create" class="modal-ajax-form ticktac-start btn', $content);
    }
}
