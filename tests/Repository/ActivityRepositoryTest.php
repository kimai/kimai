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
use App\Entity\Team;
use App\Entity\User;
use App\Repository\ActivityRepository;
use App\Repository\Query\ActivityFormTypeQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(ActivityRepository::class)]
#[Group('integration')]
class ActivityRepositoryTest extends AbstractRepositoryTestCase
{
    private function createProject(string $name, ?Team $team): Project
    {
        $em = $this->getEntityManager();

        $customer = new Customer('preselect customer ' . $name);
        $customer->setCountry('DE');
        $customer->setCurrency('EUR');
        $customer->setTimezone(date_default_timezone_get());
        $em->persist($customer);

        $project = new Project();
        $project->setName('preselect project ' . $name);
        $project->setCustomer($customer);
        $em->persist($project);

        $team?->addProject($project);

        return $project;
    }

    private function createActivity(string $name, Project $project, ?Team $team): void
    {
        $activity = new Activity();
        $activity->setName($name);
        $activity->setProject($project);
        $activity->setVisible(false);
        $this->getEntityManager()->persist($activity);

        $team?->addActivity($activity);
    }

    private function getActivityId(string $name): int
    {
        $activity = $this->getEntityManager()->getRepository(Activity::class)->findOneBy(['name' => $name]);
        self::assertInstanceOf(Activity::class, $activity);
        $id = $activity->getId();
        self::assertIsInt($id);

        return $id;
    }

    /**
     * @return array<int|null>
     */
    private function getFormTypeActivityIds(ActivityFormTypeQuery $query): array
    {
        $repository = $this->getEntityManager()->getRepository(Activity::class);
        self::assertInstanceOf(ActivityRepository::class, $repository);

        /** @var array<Activity> $activities */
        $activities = $repository->getQueryBuilderForFormType($query)->getQuery()->getResult();

        return array_map(function (Activity $activity) {
            return $activity->getId();
        }, $activities);
    }

    /**
     * A requested activity ID may skip the visibility filter, but never the team permissions
     * of the activity and its project.
     */
    public function testFormTypeQueryChecksTeamPermissionsForRequestedActivities(): void
    {
        $em = $this->getEntityManager();

        $own = new Team('preselect own team');
        $own->addUser($this->getUserByRole(User::ROLE_USER));
        $em->persist($own);
        $foreign = new Team('preselect foreign team');
        $em->persist($foreign);

        $foreignProject = $this->createProject('foreign', $foreign);
        $sharedProject = $this->createProject('shared', null);

        // the activity has no team, but its project belongs to a foreign team
        $this->createActivity('preselect activity in foreign project', $foreignProject, null);
        // the project is accessible, but the activity belongs to a foreign team
        $this->createActivity('preselect activity of foreign team', $sharedProject, $foreign);
        $this->createActivity('preselect activity of own team', $sharedProject, $own);
        $em->flush();
        $em->clear();

        $foreignProjectActivity = $this->getActivityId('preselect activity in foreign project');
        $foreignTeamActivity = $this->getActivityId('preselect activity of foreign team');
        $ownActivity = $this->getActivityId('preselect activity of own team');
        $all = [$foreignProjectActivity, $foreignTeamActivity, $ownActivity];

        $user = $this->getUserByRole(User::ROLE_USER);

        // IDs as they are taken from a request
        $query = new ActivityFormTypeQuery();
        $query->setActivities($all);
        $query->setUser($user);
        $ids = $this->getFormTypeActivityIds($query);
        self::assertNotContains($foreignProjectActivity, $ids, 'An activity of a foreign team project was added by its ID.');
        self::assertNotContains($foreignTeamActivity, $ids, 'An activity of a foreign team was added by its ID.');
        self::assertContains($ownActivity, $ids, 'An invisible activity of the own team can still be requested by its ID.');

        // the opt-in, which is used for the activity of an already stored record
        $query = new ActivityFormTypeQuery();
        $query->setActivities($all);
        $query->setUser($user);
        $query->setAllowActivityPreselect(true);
        $ids = $this->getFormTypeActivityIds($query);
        self::assertContains($foreignProjectActivity, $ids);
        self::assertContains($foreignTeamActivity, $ids);
        self::assertContains($ownActivity, $ids);

        // users who can see all data are not restricted
        $query = new ActivityFormTypeQuery();
        $query->setActivities($all);
        $query->setUser($this->getUserByRole(User::ROLE_SUPER_ADMIN));
        $ids = $this->getFormTypeActivityIds($query);
        self::assertContains($foreignProjectActivity, $ids);
        self::assertContains($foreignTeamActivity, $ids);
    }
}
