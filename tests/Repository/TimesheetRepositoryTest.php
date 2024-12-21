<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Repository\ActivityRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\TimesheetQuery;
use App\Repository\Query\TimesheetQueryHint;
use App\Repository\TimesheetRepository;
use App\Utils\Pagination;

/**
 * @covers \App\Repository\TimesheetRepository
 * @group integration
 */
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

        $result = $repository->getTimesheetsForQuery($query, true);

        self::assertTrue($query->hasQueryHint(TimesheetQueryHint::CUSTOMER_META_FIELDS));
        self::assertTrue($query->hasQueryHint(TimesheetQueryHint::PROJECT_META_FIELDS));
        self::assertTrue($query->hasQueryHint(TimesheetQueryHint::ACTIVITY_META_FIELDS));
        self::assertIsArray($result);
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
        self::assertEquals('Travel', $timesheet->getTags()->get(0)->getName());
        self::assertNotNull($timesheet->getTags()->get(0)->getId());
        self::assertEquals('Picture', $timesheet->getTags()->get(1)->getName());
        self::assertNotNull($timesheet->getTags()->get(1)->getId());
    }
}
