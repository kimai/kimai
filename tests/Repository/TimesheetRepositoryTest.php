<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Tag;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Repository\ActivityRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\TimesheetQuery;
use App\Repository\Query\TimesheetQueryHint;
use App\Repository\TimesheetRepository;
use App\Utils\Pagination;
use Doctrine\ORM\PersistentCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(TimesheetRepository::class)]
#[Group('integration')]
class TimesheetRepositoryTest extends AbstractRepositoryTestCase
{
    public function testResultTypeForQueryState(): void
    {
        $em = $this->getEntityManager();
        /** @var TimesheetRepository $repository */
        $repository = $em->getRepository(Timesheet::class);

        $query = new TimesheetQuery();

        $result = $repository->getPagerfantaForQuery($query);
        self::assertInstanceOf(Pagination::class, $result);
        self::assertFalse($query->hasQueryHint(TimesheetQueryHint::CUSTOMER_META_FIELDS));
        self::assertFalse($query->hasQueryHint(TimesheetQueryHint::PROJECT_META_FIELDS));
        self::assertFalse($query->hasQueryHint(TimesheetQueryHint::ACTIVITY_META_FIELDS));
    }

    public function testPaginationReturnsDistinctResultsWithIdenticalBegin(): void
    {
        $em = $this->getEntityManager();
        /** @var ActivityRepository $activityRepository */
        $activityRepository = $em->getRepository(Activity::class);
        $activity = $activityRepository->find(1);
        /** @var ProjectRepository $projectRepository */
        $projectRepository = $em->getRepository(Project::class);
        $project = $projectRepository->find(1);

        $user = $this->getUserByRole(User::ROLE_USER);
        /** @var TimesheetRepository $repository */
        $repository = $em->getRepository(Timesheet::class);

        // all records share the exact same begin, so the default "begin DESC"
        // order alone cannot produce a stable order across paginated queries
        $begin = new \DateTime('2015-06-15 10:00:00');
        $end = new \DateTime('2015-06-15 11:00:00');
        $amount = 20;
        for ($i = 0; $i < $amount; $i++) {
            $timesheet = new Timesheet();
            $timesheet
                ->setBegin(clone $begin)
                ->setEnd(clone $end)
                ->setUser($user)
                ->setActivity($activity)
                ->setProject($project);
            $em->persist($timesheet);
        }
        $em->flush();

        $pageSize = 5;
        $collected = [];
        $pages = (int) ceil($amount / $pageSize);
        for ($page = 1; $page <= $pages; $page++) {
            $query = new TimesheetQuery();
            $query->setUser($user);
            $query->setPage($page);
            $query->setPageSize($pageSize);
            $pager = $repository->getPagerfantaForQuery($query);
            /** @var Timesheet $timesheet */
            foreach ($pager->getCurrentPageResults() as $timesheet) {
                $collected[] = $timesheet->getId();
            }
        }

        self::assertCount($amount, $collected);
        self::assertCount($amount, array_unique($collected), 'Pagination returned the same timesheet on more than one page');
    }

    public function testSave(): void
    {
        $em = $this->getEntityManager();
        /** @var ActivityRepository $activityRepository */
        $activityRepository = $em->getRepository(Activity::class);
        $activity = $activityRepository->find(1);
        /** @var ProjectRepository $projectRepository */
        $projectRepository = $em->getRepository(Project::class);
        $project = $projectRepository->find(1);

        $user = $this->getUserByRole(User::ROLE_USER);
        /** @var TimesheetRepository $repository */
        $repository = $em->getRepository(Timesheet::class);
        $timesheet = new Timesheet();
        $timesheet
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
            ->setDescription('foo')
            ->setUser($user)
            ->setActivity($activity)
            ->setProject($project);

        self::assertNull($timesheet->getId());
        $repository->save($timesheet);
        self::assertNotNull($timesheet->getId());
    }

    public function testSaveWithTags(): void
    {
        $em = $this->getEntityManager();
        /** @var ActivityRepository $activityRepository */
        $activityRepository = $em->getRepository(Activity::class);
        $activity = $activityRepository->find(1);
        /** @var ProjectRepository $projectRepository */
        $projectRepository = $em->getRepository(Project::class);
        $project = $projectRepository->find(1);

        $user = $this->getUserByRole(User::ROLE_USER);
        /** @var TimesheetRepository $repository */
        $repository = $em->getRepository(Timesheet::class);
        $tagOne = new Tag();
        $tagOne->setName('Travel');
        $tagTwo = new Tag();
        $tagTwo->setName('Picture');
        $timesheet = new Timesheet();
        $timesheet
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
            ->setDescription('foo')
            ->setUser($user)
            ->setActivity($activity)
            ->setProject($project)
            ->addTag($tagOne)
            ->addTag($tagTwo);

        self::assertNull($timesheet->getId());
        $repository->save($timesheet);
        self::assertNotNull($timesheet->getId());
        self::assertEquals(2, $timesheet->getTags()->count());

        $tag = $timesheet->getTags()->get(0);
        self::assertInstanceOf(Tag::class, $tag);
        self::assertEquals('Travel', $tag->getName());
        self::assertNotNull($tag->getId());

        $tag = $timesheet->getTags()->get(1);
        self::assertInstanceOf(Tag::class, $tag);
        self::assertEquals('Picture', $tag->getName());
        self::assertNotNull($tag->getId());
    }

    /**
     * The voters check the teams of project, customer and activity for every rendered row.
     * If those collections are not preloaded, Doctrine resolves them one by one, which adds
     * two queries per distinct project to every listing page.
     */
    public function testTimesheetResultPreloadsTeamAssignments(): void
    {
        $em = $this->getEntityManager();

        $user = $this->getUserByRole(User::ROLE_USER);

        $team = new Team('preload team');
        $team->addTeamlead($user);
        $em->persist($team);

        $customer = new Customer('preload customer');
        $customer->setCountry('DE');
        $customer->setTimezone('Europe/Berlin');
        $team->addCustomer($customer);
        $em->persist($customer);

        $project = new Project();
        $project->setName('preload project');
        $project->setCustomer($customer);
        $team->addProject($project);
        $em->persist($project);

        $activity = new Activity();
        $activity->setName('preload activity');
        $activity->setProject($project);
        $team->addActivity($activity);
        $em->persist($activity);

        $em->flush();

        $timesheet = new Timesheet();
        $timesheet->setBegin(new \DateTime('2021-04-01 10:00:00'))
            ->setEnd(new \DateTime('2021-04-01 11:00:00'))
            ->setUser($user)
            ->setProject($project)
            ->setActivity($activity);
        $em->persist($timesheet);
        $em->flush();
        $em->clear();

        /** @var TimesheetRepository $repository */
        $repository = $em->getRepository(Timesheet::class);

        $query = new TimesheetQuery();
        $query->setUser($this->getUserByRole(User::ROLE_USER));

        $results = $repository->getTimesheetResult($query)->getResults();
        self::assertNotEmpty($results);

        foreach ($results as $loaded) {
            $loadedProject = $loaded->getProject();
            self::assertInstanceOf(Project::class, $loadedProject);
            self::assertInstanceOf(PersistentCollection::class, $loadedProject->getTeams());
            self::assertTrue($loadedProject->getTeams()->isInitialized(), 'Project teams were not preloaded');

            $loadedCustomer = $loadedProject->getCustomer();
            self::assertInstanceOf(Customer::class, $loadedCustomer);
            self::assertInstanceOf(PersistentCollection::class, $loadedCustomer->getTeams());
            self::assertTrue($loadedCustomer->getTeams()->isInitialized(), 'Customer teams were not preloaded');

            $loadedActivity = $loaded->getActivity();
            self::assertInstanceOf(Activity::class, $loadedActivity);
            self::assertInstanceOf(PersistentCollection::class, $loadedActivity->getTeams());
            self::assertTrue($loadedActivity->getTeams()->isInitialized(), 'Activity teams were not preloaded');
        }
    }

    /**
     * Regression test for GHSA-c6j4-35fc-x3hw.
     *
     * The permission criteria only limited the results by project and customer
     * teams. Timesheets using an activity, which is limited to a team the current
     * user is not part of, were returned by the listing - although the voter
     * (and therefore every by-id route) rejects exactly those records.
     */
    public function testQueryDoesNotReturnTimesheetsWithForeignActivityTeam(): void
    {
        $em = $this->getEntityManager();

        $teamlead = $this->getUserByRole(User::ROLE_TEAMLEAD);
        $member = $this->getUserByRole(User::ROLE_USER);

        $ownTeam = new Team('GHSA-c6j4 own team');
        $ownTeam->addTeamlead($teamlead);
        $ownTeam->addUser($member);
        $em->persist($ownTeam);

        $foreignTeam = new Team('GHSA-c6j4 foreign team');
        $foreignTeam->addTeamlead($member);
        $em->persist($foreignTeam);

        $customer = new Customer('GHSA-c6j4 customer');
        $customer->setCountry('DE');
        $customer->setTimezone('Europe/Berlin');
        $em->persist($customer);

        $project = new Project();
        $project->setName('GHSA-c6j4 project');
        $project->setCustomer($customer);
        $em->persist($project);

        $visibleActivity = new Activity();
        $visibleActivity->setName('GHSA-c6j4 visible activity');
        $visibleActivity->setProject($project);
        $em->persist($visibleActivity);

        // only the foreign team may access this activity
        $restrictedActivity = new Activity();
        $restrictedActivity->setName('GHSA-c6j4 restricted activity');
        $restrictedActivity->setProject($project);
        $foreignTeam->addActivity($restrictedActivity);
        $em->persist($restrictedActivity);

        $em->flush();

        $visible = new Timesheet();
        $visible->setBegin(new \DateTime('2020-01-01 10:00:00'))
            ->setEnd(new \DateTime('2020-01-01 11:00:00'))
            ->setUser($member)
            ->setProject($project)
            ->setActivity($visibleActivity);
        $em->persist($visible);

        $restricted = new Timesheet();
        $restricted->setBegin(new \DateTime('2020-01-01 12:00:00'))
            ->setEnd(new \DateTime('2020-01-01 13:00:00'))
            ->setUser($member)
            ->setProject($project)
            ->setActivity($restrictedActivity);
        $em->persist($restricted);

        $em->flush();
        $em->clear();

        $teamlead = $this->getUserByRole(User::ROLE_TEAMLEAD);

        /** @var TimesheetRepository $repository */
        $repository = $em->getRepository(Timesheet::class);

        $query = new TimesheetQuery();
        $query->setCurrentUser($teamlead);

        $found = [];
        foreach ($repository->getPagerfantaForQuery($query)->getCurrentPageResults() as $timesheet) {
            self::assertInstanceOf(Timesheet::class, $timesheet);
            $found[] = $timesheet->getId();
        }

        self::assertContains($visible->getId(), $found);
        self::assertNotContains($restricted->getId(), $found);
    }

    /**
     * The same boundary has to be enforced when timesheets are fetched by ID,
     * see GHSA-c6j4-35fc-x3hw.
     */
    public function testFindTimesheetsByIdDoesNotReturnForeignActivityTeam(): void
    {
        $em = $this->getEntityManager();

        $teamlead = $this->getUserByRole(User::ROLE_TEAMLEAD);
        $member = $this->getUserByRole(User::ROLE_USER);

        $foreignTeam = new Team('GHSA-c6j4 foreign team by id');
        $foreignTeam->addTeamlead($member);
        $em->persist($foreignTeam);

        $customer = new Customer('GHSA-c6j4 customer by id');
        $customer->setCountry('DE');
        $customer->setTimezone('Europe/Berlin');
        $em->persist($customer);

        $project = new Project();
        $project->setName('GHSA-c6j4 project by id');
        $project->setCustomer($customer);
        $em->persist($project);

        $restrictedActivity = new Activity();
        $restrictedActivity->setName('GHSA-c6j4 restricted activity by id');
        $restrictedActivity->setProject($project);
        $foreignTeam->addActivity($restrictedActivity);
        $em->persist($restrictedActivity);

        $em->flush();

        $restricted = new Timesheet();
        $restricted->setBegin(new \DateTime('2020-02-01 12:00:00'))
            ->setEnd(new \DateTime('2020-02-01 13:00:00'))
            ->setUser($member)
            ->setProject($project)
            ->setActivity($restrictedActivity);
        $em->persist($restricted);

        $em->flush();
        $restrictedId = $restricted->getId();
        self::assertIsInt($restrictedId);
        $em->clear();

        $teamlead = $this->getUserByRole(User::ROLE_TEAMLEAD);

        /** @var TimesheetRepository $repository */
        $repository = $em->getRepository(Timesheet::class);

        self::assertCount(0, $repository->findTimesheetsById($teamlead, [$restrictedId]));
    }
}
