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
use App\Repository\TimesheetRepository;
use App\Utils\Pagination;

/**
 * @covers \App\Repository\TimesheetRepository
 * @group integration
 */
class TimesheetRepositoryTest extends AbstractRepositoryTest
{
    public function testResultTypeForQueryState()
    {
        $em = $this->getEntityManager();
        /** @var TimesheetRepository $repository */
        $repository = $em->getRepository(Timesheet::class);

        $query = new TimesheetQuery();

        $result = $repository->getPagerfantaForQuery($query);
        $this->assertInstanceOf(Pagination::class, $result);

        $result = $repository->getTimesheetsForQuery($query);
        $this->assertIsArray($result);
    }

    public function testSave()
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

        $this->assertNull($timesheet->getId());
        $repository->save($timesheet);
        $this->assertNotNull($timesheet->getId());
    }

    public function testSaveWithTags()
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

        $this->assertNull($timesheet->getId());
        $repository->save($timesheet);
        $this->assertNotNull($timesheet->getId());
        $this->assertEquals(2, $timesheet->getTags()->count());
        $this->assertEquals('Travel', $timesheet->getTags()->get(0)->getName());
        $this->assertNotNull($timesheet->getTags()->get(0)->getId());
        $this->assertEquals('Picture', $timesheet->getTags()->get(1)->getName());
        $this->assertNotNull($timesheet->getTags()->get(1)->getId());
    }
}
