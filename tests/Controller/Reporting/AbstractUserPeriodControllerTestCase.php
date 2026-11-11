<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Reporting;

use App\Entity\Project;
use App\Entity\Team;
use App\Entity\User;
use App\Tests\Controller\AbstractControllerBaseTestCase;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\TeamRestrictedTimesheetTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Group('integration')]
abstract class AbstractUserPeriodControllerTestCase extends AbstractControllerBaseTestCase
{
    use TeamRestrictedTimesheetTrait;

    protected function importReportingFixture(string $role): void
    {
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(50);
        $fixture->setAmountRunning(10);
        $fixture->setUser($this->getUserByRole($role));
        $fixture->setStartDate(new \DateTime());
        $this->importFixture($fixture);
    }

    abstract protected function getReportUrl(): string;

    abstract protected function getExportUrl(): string;

    abstract protected function getBoxId(): string;

    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured($this->getReportUrl());
    }

    public static function getTestData(): array
    {
        return [
            [4, 'duration', 'Total'],
            [4, 'rate', 'Total revenue'],
            [4, 'internalRate', 'Internal price'],
        ];
    }

    #[DataProvider('getTestData')]
    public function testUserPeriodReport(int $user, string $dataType, string $title): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->importReportingFixture(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, \sprintf('%s?user=%s&date=12999119191&sumType=%s', $this->getReportUrl(), $user, $dataType));
        self::assertStringContainsString(\sprintf('<div class="card-body p-0 %s', $this->getBoxId()), $client->getResponse()->getContent());
        $option = $client->getCrawler()->filterXPath("//select[@id='user']/option[@selected]");
        self::assertEquals($user, $option->attr('value'));
        $cell = $client->getCrawler()->filterXPath("//th[contains(@class, 'reportDataTypeTitle')]");
        self::assertEquals($title, $cell->text());
    }

    #[DataProvider('getTestData')]
    public function testUserPeriodReportExport(int $user, string $dataType, string $title): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->importReportingFixture(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, \sprintf('%s?user=%s&date=2023-12-23&sumType=%s', $this->getExportUrl(), $user, $dataType));

        $response = $client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertInstanceOf(BinaryFileResponse::class, $response);

        // temporary file will be deleted!
        $file = $response->getFile();
        self::assertFileDoesNotExist($response->getFile());

        $disposition = $response->headers->get('Content-Disposition');
        self::assertIsString($disposition);
        self::assertNotEmpty($disposition);

        self::assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment; filename=kimai-export-user-', $disposition);
    }

    public function testUserPeriodReportAsTeamlead(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importReportingFixture(User::ROLE_USER);
        $this->assertAccessIsGranted($client, \sprintf('%s?date=12999119191', $this->getReportUrl()));
        self::assertStringContainsString(\sprintf('<div class="card-body p-0 %s', $this->getBoxId()), $client->getResponse()->getContent());
        $select = $client->getCrawler()->filterXPath("//select[@id='user']");
        self::assertEquals(0, $select->count());
        $cell = $client->getCrawler()->filterXPath("//th[contains(@class, 'reportDataTypeTitle')]");
        self::assertEquals('Total', $cell->text());
    }

    /**
     * A teamlead can select the members of his teams, but he must not see their records for
     * projects and activities, which are limited to teams he is not part of.
     */
    public function testUserPeriodReportHidesRecordsOfForeignTeams(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        [$teamlead, $member] = $this->createTeamRestrictedTimesheets();
        $query = \sprintf('?user=%s&date=2020-05-13', $member->getId());

        $this->assertAccessIsGranted($client, $this->getReportUrl() . $query);
        $option = $client->getCrawler()->filterXPath("//select[@id='user']/option[@selected]");
        self::assertEquals($member->getId(), $option->attr('value'));
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('Own project', $content);
        self::assertStringContainsString('Own activity', $content);
        self::assertStringNotContainsString('Foreign project', $content);
        self::assertStringNotContainsString('Foreign activity', $content);
        self::assertStringNotContainsString('Open project', $content);
        self::assertStringNotContainsString('Restricted activity', $content);

        $this->assertAccessIsGranted($client, $this->getExportUrl() . $query);
        $content = $this->getExcelExportContent($client);
        self::assertStringContainsString('Own project', $content);
        self::assertStringNotContainsString('Foreign project', $content);
        self::assertStringNotContainsString('Restricted activity', $content);
    }

    /**
     * Control for testUserPeriodReportHidesRecordsOfForeignTeams(): users see all of their own
     * records, even if they do not have access to the project (like in their own timesheet).
     */
    public function testUserPeriodReportShowsOwnRecordsOfForeignTeams(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        [$teamlead, $member] = $this->createTeamRestrictedTimesheets();
        $query = \sprintf('?user=%s&date=2020-05-13', $teamlead->getId());

        $this->assertAccessIsGranted($client, $this->getReportUrl() . $query);
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('Lost project', $content);
        self::assertStringContainsString('Lost activity', $content);

        $this->assertAccessIsGranted($client, $this->getExportUrl() . $query);
        self::assertStringContainsString('Lost project', $this->getExcelExportContent($client));
    }

    /**
     * Control for testUserPeriodReportHidesRecordsOfForeignTeams(): users with the
     * permission to see all data are not limited.
     */
    public function testUserPeriodReportShowsRecordsOfForeignTeamsToAdmin(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        [$teamlead, $member] = $this->createTeamRestrictedTimesheets();

        $this->assertAccessIsGranted($client, \sprintf('%s?user=%s&date=2020-05-13', $this->getReportUrl(), $member->getId()));
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('Own project', $content);
        self::assertStringContainsString('Foreign project', $content);
        self::assertStringContainsString('Restricted activity', $content);
    }

    /**
     * The record was created for a project of the own team, which was moved to another team later on.
     */
    public function testUserPeriodReportHidesRecordsAfterProjectWasMovedToForeignTeam(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        [$ownTeam, $foreignTeam] = $this->createOwnAndForeignTeam();
        $member = $this->getUserByRole(User::ROLE_USER);
        $timesheet = $this->createTeamTimesheet($member, 'Moved project', 'Moved activity', [$ownTeam]);
        $projectId = $timesheet->getProject()?->getId();
        $url = \sprintf('%s?user=%s&date=2020-05-13', $this->getReportUrl(), $member->getId());

        $this->assertAccessIsGranted($client, $url);
        self::assertStringContainsString('Moved project', (string) $client->getResponse()->getContent());

        $em = $this->getEntityManager();
        $project = $em->getRepository(Project::class)->find($projectId);
        self::assertInstanceOf(Project::class, $project);
        $ownTeam = $em->getRepository(Team::class)->find($ownTeam->getId());
        self::assertInstanceOf(Team::class, $ownTeam);
        $foreignTeam = $em->getRepository(Team::class)->find($foreignTeam->getId());
        self::assertInstanceOf(Team::class, $foreignTeam);
        $ownTeam->removeProject($project);
        $em->flush();
        $foreignTeam->addProject($project);
        $em->persist($project);
        $em->flush();
        self::assertEquals(
            [['team_id' => $foreignTeam->getId()]],
            $em->getConnection()->fetchAllAssociative('SELECT team_id FROM kimai2_projects_teams WHERE project_id = ?', [$projectId])
        );

        $this->assertAccessIsGranted($client, $url);
        $option = $client->getCrawler()->filterXPath("//select[@id='user']/option[@selected]");
        self::assertEquals($member->getId(), $option->attr('value'));
        self::assertStringNotContainsString('Moved project', (string) $client->getResponse()->getContent());
    }

    /**
     * The records were created by a member of another team, who joined the own team later on.
     */
    public function testUserPeriodReportHidesRecordsOfNewTeamMember(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        [$ownTeam, $foreignTeam] = $this->createOwnAndForeignTeam(false);
        $member = $this->getUserByRole(User::ROLE_USER);
        $this->createTeamTimesheet($member, 'Foreign project', 'Foreign activity', [$foreignTeam]);
        $this->createTeamTimesheet($member, 'Open project', 'Open activity');
        $url = \sprintf('%s?user=%s&date=2020-05-13', $this->getReportUrl(), $member->getId());

        // not a team member yet: the user cannot be selected
        $this->assertAccessIsGranted($client, $url);
        $option = $client->getCrawler()->filterXPath(\sprintf("//select[@id='user']/option[@value='%s']", $member->getId()));
        self::assertEquals(0, $option->count());
        self::assertStringNotContainsString('Foreign project', (string) $client->getResponse()->getContent());

        $em = $this->getEntityManager();
        $member = $this->getUserByRole(User::ROLE_USER);
        $ownTeam = $em->getRepository(Team::class)->find($ownTeam->getId());
        self::assertInstanceOf(Team::class, $ownTeam);
        $foreignTeam = $em->getRepository(Team::class)->find($foreignTeam->getId());
        self::assertInstanceOf(Team::class, $foreignTeam);
        $foreignTeam->removeUser($member);
        $em->flush();
        $ownTeam->addUser($member);
        $em->persist($ownTeam);
        $em->flush();

        $this->assertAccessIsGranted($client, $url);
        $option = $client->getCrawler()->filterXPath("//select[@id='user']/option[@selected]");
        self::assertEquals($member->getId(), $option->attr('value'));
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('Open project', $content);
        self::assertStringNotContainsString('Foreign project', $content);
    }
}
