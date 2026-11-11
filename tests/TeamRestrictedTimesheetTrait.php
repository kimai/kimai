<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;

/**
 * Fixtures for tests, which make sure that a teamlead does not see the records of
 * his team members for projects and activities of teams he is not part of.
 */
trait TeamRestrictedTimesheetTrait
{
    /**
     * The "own team" is led by the ROLE_TEAMLEAD user, the ROLE_USER user is a member of it.
     * The "foreign team" is led by the ROLE_ADMIN user, the ROLE_TEAMLEAD user is not part of it.
     *
     * @return array{0: Team, 1: Team}
     */
    protected function createOwnAndForeignTeam(bool $memberInOwnTeam = true): array
    {
        $em = $this->getEntityManager();
        $member = $this->getUserByRole(User::ROLE_USER);

        $ownTeam = new Team('Own team');
        $ownTeam->addTeamlead($this->getUserByRole(User::ROLE_TEAMLEAD));
        $em->persist($ownTeam);

        $foreignTeam = new Team('Foreign team');
        $foreignTeam->addTeamlead($this->getUserByRole(User::ROLE_ADMIN));
        $em->persist($foreignTeam);

        if ($memberInOwnTeam) {
            $ownTeam->addUser($member);
        } else {
            $foreignTeam->addUser($member);
        }

        $em->flush();

        return [$ownTeam, $foreignTeam];
    }

    /**
     * Creates a stopped record with a duration of one hour on wednesday 2020-05-13,
     * using a new project and activity, which are limited to the given teams.
     *
     * @param array<Team> $projectTeams
     * @param array<Team> $activityTeams
     */
    protected function createTeamTimesheet(User $user, string $project, string $activity, array $projectTeams = [], array $activityTeams = []): Timesheet
    {
        $em = $this->getEntityManager();

        // customers are not limited to a team and can show up in dropdowns: do not re-use the project name
        $customer = new Customer(str_replace(' project', ' customer', $project));
        $customer->setCountry('DE');
        $customer->setTimezone('Europe/Berlin');
        $em->persist($customer);

        $projectEntity = new Project();
        $projectEntity->setName($project);
        $projectEntity->setCustomer($customer);
        foreach ($projectTeams as $team) {
            $team->addProject($projectEntity);
        }
        $em->persist($projectEntity);

        $activityEntity = new Activity();
        $activityEntity->setName($activity);
        $activityEntity->setProject($projectEntity);
        foreach ($activityTeams as $team) {
            $team->addActivity($activityEntity);
        }
        $em->persist($activityEntity);

        $timesheet = new Timesheet();
        $timesheet->setUser($user);
        $timesheet->setProject($projectEntity);
        $timesheet->setActivity($activityEntity);
        $timesheet->setBegin(new \DateTime('2020-05-13 10:00:00'));
        $timesheet->setEnd(new \DateTime('2020-05-13 11:00:00'));
        $timesheet->setDuration(3600);
        $em->persist($timesheet);

        $em->flush();

        return $timesheet;
    }

    /**
     * The team member has three records: one for a project of the own team, one for a project
     * of the foreign team and one for an activity of the foreign team.
     * The teamlead has one record for another project of the foreign team.
     *
     * @return array{0: User, 1: User}
     */
    protected function createTeamRestrictedTimesheets(): array
    {
        [$ownTeam, $foreignTeam] = $this->createOwnAndForeignTeam();

        $teamlead = $this->getUserByRole(User::ROLE_TEAMLEAD);
        $member = $this->getUserByRole(User::ROLE_USER);

        $this->createTeamTimesheet($member, 'Own project', 'Own activity', [$ownTeam]);
        $this->createTeamTimesheet($member, 'Foreign project', 'Foreign activity', [$foreignTeam]);
        $this->createTeamTimesheet($member, 'Open project', 'Restricted activity', [], [$foreignTeam]);
        $this->createTeamTimesheet($teamlead, 'Lost project', 'Lost activity', [$foreignTeam]);

        return [$teamlead, $member];
    }
}
