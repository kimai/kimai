<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\SecurityTesting;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Tests\API\APIControllerBaseTestCase;
use App\Tests\DataFixtures\TimesheetFixtures;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security tests for OWASP WSTG v4.2 category "4.5 Authorization Testing" (WSTG-ATHZ).
 *
 * Covers insecure direct object references (IDOR), horizontal privilege escalation
 * and vertical privilege escalation through the JSON API.
 */
#[Group('integration')]
#[Group('security')]
class AuthorizationSecurityTest extends APIControllerBaseTestCase
{
    /**
     * @return Timesheet[]
     */
    private function importTimesheetsForRole(string $role, int $amount = 1): array
    {
        $fixture = new TimesheetFixtures($this->getUserByRole($role), $amount);
        $fixture->setStartDate(new \DateTime('-10 days'));

        return $this->importFixture($fixture);
    }

    /**
     * WSTG-ATHZ-03 (IDOR):
     * A user must not read timesheet records of other users by guessing object IDs.
     */
    public function testUserCannotReadOtherUsersTimesheetById(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $timesheets = $this->importTimesheetsForRole(User::ROLE_ADMIN);

        $this->request($client, '/api/timesheets/' . $timesheets[0]->getId());

        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
    }

    /**
     * WSTG-ATHZ-03 (IDOR):
     * Modification attempts on foreign objects must be rejected as well,
     * not only read access.
     */
    public function testUserCannotModifyOtherUsersTimesheetById(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $timesheets = $this->importTimesheetsForRole(User::ROLE_ADMIN);

        $json = json_encode(['description' => 'IDOR modification attempt']);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets/' . $timesheets[0]->getId(), 'PATCH', [], $json);

        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
    }

    /**
     * WSTG-ATHZ-04 (horizontal privilege escalation):
     * A regular user must not browse foreign user profiles.
     */
    public function testUserCannotViewOtherUsersProfile(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        // user ID 4 is the teamlead fixture account
        $this->request($client, '/api/users/4');
        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
    }

    /**
     * WSTG-ATHZ-02 (vertical privilege escalation):
     * Creating accounts (here with super-admin role) requires administrative rights.
     */
    public function testUserCannotCreateNewUserAccounts(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $data = [
            'username' => 'privesc_attempt',
            'email' => 'privesc@example.com',
            'plainPassword' => 'Secret123!',
            'roles' => [User::ROLE_SUPER_ADMIN],
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/users', 'POST', [], $json);

        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
    }

    /**
     * WSTG-ATHZ-02:
     * Lower privileged roles must not reach administrative API endpoints.
     */
    #[DataProvider('provideRestrictedEndpoints')]
    public function testRestrictedEndpointsRejectLowerRoles(string $role, string $url, string $method): void
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $this->request($client, $url, $method);

        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $client->getResponse()->getStatusCode(),
            \sprintf('Endpoint %s %s is not restricted for role %s', $method, $url, $role)
        );
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function provideRestrictedEndpoints(): array
    {
        return [
            'user collection as user' => [User::ROLE_USER, '/api/users', 'GET'],
            'user collection as teamlead' => [User::ROLE_TEAMLEAD, '/api/users', 'GET'],
            'create customer as user' => [User::ROLE_USER, '/api/customers', 'POST'],
        ];
    }
}
