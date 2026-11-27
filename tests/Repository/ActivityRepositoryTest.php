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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(ActivityRepository::class)]
#[Group('integration')]
class ActivityRepositoryTest extends AbstractRepositoryTestCase
{
    /**
     * @return array{own: Activity, hidden: Activity, foreign: Activity, foreignProject: Activity, foreignGlobal: Activity}
     */
    private function createTeamActivities(User $user): array
    {
        $em = $this->getEntityManager();

        $ownTeam = new Team('activity form type own team');
        $ownTeam->addUser($user);
        $em->persist($ownTeam);

        $foreignTeam = new Team('activity form type foreign team');
        $em->persist($foreignTeam);

        $customer = new Customer('activity form type customer');
        $customer->setCountry('DE');
        $customer->setTimezone('Europe/Berlin');
        $em->persist($customer);

        $openProject = new Project();
        $openProject->setName('activity form type open project');
        $openProject->setCustomer($customer);
        $em->persist($openProject);

        $foreignProject = new Project();
        $foreignProject->setName('activity form type foreign project');
        $foreignProject->setCustomer($customer);
        $foreignTeam->addProject($foreignProject);
        $em->persist($foreignProject);

        $own = new Activity();
        $own->setName('activity form type own global');
        $em->persist($own);

        $hidden = new Activity();
        $hidden->setName('activity form type hidden global');
        $hidden->setVisible(false);
        $em->persist($hidden);

        // restricted on activity level
        $foreign = new Activity();
        $foreign->setName('activity form type foreign activity');
        $foreign->setProject($openProject);
        $foreignTeam->addActivity($foreign);
        $em->persist($foreign);

        // unrestricted activity, but restricted on project level
        $foreignProjectActivity = new Activity();
        $foreignProjectActivity->setName('activity form type activity of foreign project');
        $foreignProjectActivity->setProject($foreignProject);
        $em->persist($foreignProjectActivity);

        // global activity, restricted on activity level
        $foreignGlobal = new Activity();
        $foreignGlobal->setName('activity form type foreign global');
        $foreignTeam->addActivity($foreignGlobal);
        $em->persist($foreignGlobal);

        $em->flush();

        return [
            'own' => $own,
            'hidden' => $hidden,
            'foreign' => $foreign,
            'foreignProject' => $foreignProjectActivity,
            'foreignGlobal' => $foreignGlobal,
        ];
    }

    /**
     * @return array<int|null>
     */
    private function findActivityIdsForFormType(ActivityFormTypeQuery $query): array
    {
        /** @var ActivityRepository $repository */
        $repository = $this->getEntityManager()->getRepository(Activity::class);

        return array_map(fn (Activity $activity) => $activity->getId(), $repository->getQueryBuilderForFormType($query)->getQuery()->getResult());
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function getForeignActivities(): \Generator
    {
        yield 'restricted activity' => ['foreign'];
        yield 'activity of restricted project' => ['foreignProject'];
        yield 'restricted global activity' => ['foreignGlobal'];
    }

    /**
     * IDs passed to the "include" option must not bypass the team permission criteria.
     * The IDs are passed as integers, as the toolbar forms do with the submitted request data.
     */
    #[DataProvider('getForeignActivities')]
    public function testFormTypeQueryDoesNotIncludeActivitiesOfForeignTeam(string $key): void
    {
        $user = $this->getUserByRole(User::ROLE_USER);
        $activities = $this->createTeamActivities($user);

        $query = new ActivityFormTypeQuery((int) $activities[$key]->getId());
        $query->setUser($user);

        $ids = $this->findActivityIdsForFormType($query);

        self::assertContains($activities['own']->getId(), $ids);
        self::assertNotContains($activities[$key]->getId(), $ids, 'Activity of a foreign team was included');
    }

    /**
     * Guard for the intended use of the "include" option: allow a hidden (but accessible) activity, e.g. for existing records.
     */
    public function testFormTypeQueryIncludesHiddenActivity(): void
    {
        $user = $this->getUserByRole(User::ROLE_USER);
        $activities = $this->createTeamActivities($user);

        $query = new ActivityFormTypeQuery();
        $query->setUser($user);
        self::assertNotContains($activities['hidden']->getId(), $this->findActivityIdsForFormType($query));

        $query->addActivity((int) $activities['hidden']->getId());
        self::assertContains($activities['hidden']->getId(), $this->findActivityIdsForFormType($query));
    }
}
