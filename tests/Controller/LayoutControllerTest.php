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
class LayoutControllerTest extends ControllerBaseTest
{
    public function testNavigationMenus(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $user = $this->getUserByRole(User::ROLE_USER);

        $this->request($client, '/dashboard/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasMainHeader($client, $user);
        $this->assertHasNavigation($client);
    }

    protected function assertHasMainHeader(HttpKernelBrowser $client, User $user): void
    {
        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('data-bs-toggle="dropdown" aria-label="Open personal menu"', $content);
        $this->assertStringContainsString('href="/en/profile/' . $user->getUserIdentifier() . '"', $content);
        $this->assertStringContainsString('href="/en/profile/' . $user->getUserIdentifier() . '/edit"', $content);
        $this->assertStringContainsString('href="/en/profile/' . $user->getUserIdentifier() . '/prefs"', $content);
        $this->assertStringContainsString('href="/en/logout', $content);
    }

    protected function assertHasNavigation(HttpKernelBrowser $client): void
    {
        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('href="/en/dashboard/"', $content);
        $this->assertStringContainsString('href="/en/timesheet/"', $content);
        $this->assertStringContainsString('My times', $content);
        $this->assertStringContainsString('href="/en/calendar/"', $content);
        $this->assertStringContainsString('Calendar', $content);
    }

    public function testActiveEntries(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->request($client, '/dashboard/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('<a title="Start time-tracking" href="/en/timesheet/create" class="modal-ajax-form ticktac-start btn', $content);
    }
}
