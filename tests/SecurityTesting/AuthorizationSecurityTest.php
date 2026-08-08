<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\SecurityTesting;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Tests\API\APIControllerBaseTestCase;
use App\Tests\DataFixtures\CustomerFixtures;
use App\Tests\DataFixtures\InvoiceFixtures;
use App\Tests\DataFixtures\ProjectFixtures;
use App\Tests\DataFixtures\TeamFixtures;
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
     * WSTG-ATHZ-03 (IDOR):
     * Customers are scoped by team membership, not ownership. A plain user with
     * no team association must not read one by guessing its ID.
     *
     * Expected denial is pinned by CustomerVoterTest::testVote, which asserts
     * ACCESS_DENIED on "view" for ROLE_USER against an unaffiliated customer.
     */
    public function testUserCannotReadUnaffiliatedCustomerById(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        /** @var Customer[] $customers */
        $customers = $this->importFixture(new CustomerFixtures(1));

        $this->request($client, '/api/customers/' . $customers[0]->getId());

        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $client->getResponse()->getStatusCode(),
            'A user without team access could read a foreign customer'
        );
    }

    /**
     * WSTG-ATHZ-03 (IDOR):
     * Same guarantee for projects, which are scoped through their customer's
     * teams as well as their own.
     *
     * Expected denial is pinned by ProjectVoterTest::testVote.
     */
    public function testUserCannotReadUnaffiliatedProjectById(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        /** @var Project[] $projects */
        $projects = $this->importFixture(new ProjectFixtures(1));

        $this->request($client, '/api/projects/' . $projects[0]->getId());

        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $client->getResponse()->getStatusCode(),
            'A user without team access could read a foreign project'
        );
    }

    /**
     * WSTG-ATHZ-03 (IDOR):
     * Invoices carry billing totals and customer data, and are scoped through the
     * customer's teams. A plain user must not read one by guessing its ID.
     *
     * This closes the last uncovered object type in tests/SecurityTesting/: the
     * suite previously had no invoice reference at all, so a dropped customer
     * scope on the invoice endpoints passed the entire --group security run.
     */
    public function testUserCannotReadUnaffiliatedInvoiceById(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $fixture = new InvoiceFixtures();
        $fixture->setAmount(1);
        /** @var Invoice[] $invoices */
        $invoices = $this->importFixture($fixture);

        $this->request($client, '/api/invoices/' . $invoices[0]->getId());

        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $client->getResponse()->getStatusCode(),
            'A user without team access could read a foreign invoice'
        );
    }

    /**
     * WSTG-ATHZ-03 (IDOR) / WSTG-ATHZ-02:
     * A teamlead must not edit a team they do not lead.
     *
     * What actually denies this: TeamVoter:59 lets admins and super-admins
     * through unconditionally and otherwise requires isTeamleadOf($team); the
     * `edit_team` role permission is then checked, and ROLE_TEAMLEAD does not
     * hold it (config/packages/kimai.yaml grants the TEAMS group only to
     * ROLE_ADMIN and above).
     *
     * Measured scope of this test: it does NOT detect removal of the object
     * argument from the attribute. Verified by rewriting the guard on
     * patchAction to `#[IsGranted('edit_team')]` - this test still passed.
     * Dropping the object scope changes nothing under the default role setup,
     * because the only accounts it would newly admit are those holding
     * `edit_team` without being admin, and no shipped role does. What this test
     * does catch is a role-configuration change that grants TEAMS to
     * ROLE_TEAMLEAD, which would open foreign-team edits immediately.
     */
    public function testTeamleadCannotEditForeignTeam(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $fixture = new TeamFixtures();
        $fixture->setAmount(1);
        $fixture->setAddCustomer(false);
        // guarantee the acting user is neither lead nor member of the new team
        $fixture->addUserToIgnore($this->getUserByRole(User::ROLE_TEAMLEAD));
        /** @var Team[] $teams */
        $teams = $this->importFixture($fixture);

        $json = json_encode(['name' => 'renamed-by-outsider']);
        self::assertIsString($json);
        $this->request($client, '/api/teams/' . $teams[0]->getId(), 'PATCH', [], $json);

        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $client->getResponse()->getStatusCode(),
            'A teamlead could edit a team they do not lead'
        );
    }

    /**
     * WSTG-ATHZ-04 (horizontal privilege escalation):
     * A regular user must not browse foreign user profiles.
     */
    public function testUserCannotViewOtherUsersProfile(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        // resolved from the fixture set rather than hard-coded: the literal ID
        // silently points at a different account if the seeding order changes
        $foreignUserId = $this->getAuthenticatedUserId(User::ROLE_TEAMLEAD);

        $this->request($client, '/api/users/' . $foreignUserId);
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
            // user administration
            'user collection as user' => [User::ROLE_USER, '/api/users', 'GET'],
            'user collection as teamlead' => [User::ROLE_TEAMLEAD, '/api/users', 'GET'],
            'create user as teamlead' => [User::ROLE_TEAMLEAD, '/api/users', 'POST'],
            // master data creation - administrative in the default role setup
            'create customer as user' => [User::ROLE_USER, '/api/customers', 'POST'],
            'create customer as teamlead' => [User::ROLE_TEAMLEAD, '/api/customers', 'POST'],
            'create project as user' => [User::ROLE_USER, '/api/projects', 'POST'],
            'create activity as user' => [User::ROLE_USER, '/api/activities', 'POST'],
            // team administration
            'team collection as user' => [User::ROLE_USER, '/api/teams', 'GET'],
            'create team as user' => [User::ROLE_USER, '/api/teams', 'POST'],
        ];
    }
}
