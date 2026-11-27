<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Entity\User;
use App\Entity\WorkingTime;
use App\Repository\WorkingTimeRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(WorkingTimeRepository::class)]
#[Group('integration')]
class WorkingTimeRepositoryTest extends AbstractRepositoryTestCase
{
    private function getRepository(): WorkingTimeRepository
    {
        $repository = $this->getEntityManager()->getRepository(WorkingTime::class);
        self::assertInstanceOf(WorkingTimeRepository::class, $repository);

        return $repository;
    }

    private function createWorkingTime(User $user, string $date): WorkingTime
    {
        $workingTime = new WorkingTime($user, new \DateTimeImmutable($date));
        $workingTime->setExpectedTime(28800);
        $workingTime->setActualTime(27000);
        $this->getEntityManager()->persist($workingTime);

        return $workingTime;
    }

    public function testFindForYearAndUsers(): void
    {
        $user = $this->getUserByRole(User::ROLE_USER);
        $teamlead = $this->getUserByRole(User::ROLE_TEAMLEAD);
        $admin = $this->getUserByRole(User::ROLE_ADMIN);

        $this->createWorkingTime($user, '2024-05-01');
        $this->createWorkingTime($user, '2024-01-02');
        $this->createWorkingTime($user, '2023-12-31');
        $this->createWorkingTime($user, '2025-01-01');
        $this->createWorkingTime($teamlead, '2024-02-02');
        $this->createWorkingTime($admin, '2024-03-03');
        $this->getEntityManager()->flush();

        $result = $this->getRepository()->findForYearAndUsers([$user, $teamlead], new \DateTime('2024-06-01'));

        self::assertCount(2, $result);
        self::assertArrayHasKey($user->getId(), $result);
        self::assertArrayHasKey($teamlead->getId(), $result);
        self::assertArrayNotHasKey($admin->getId(), $result);

        $userTimes = $result[$user->getId()];
        self::assertCount(2, $userTimes);
        self::assertEquals('2024-01-02', $userTimes[0]->getDate()->format('Y-m-d'));
        self::assertEquals('2024-05-01', $userTimes[1]->getDate()->format('Y-m-d'));
        self::assertSame($user, $userTimes[0]->getUser());

        $teamleadTimes = $result[$teamlead->getId()];
        self::assertCount(1, $teamleadTimes);
        self::assertEquals('2024-02-02', $teamleadTimes[0]->getDate()->format('Y-m-d'));
    }

    public function testFindForYearAndUsersWithoutResults(): void
    {
        $repository = $this->getRepository();

        self::assertSame([], $repository->findForYearAndUsers([], new \DateTime('2024-06-01')));
        // users without ID are ignored
        self::assertSame([], $repository->findForYearAndUsers([new User()], new \DateTime('2024-06-01')));
        // no working times
        self::assertSame([], $repository->findForYearAndUsers([$this->getUserByRole(User::ROLE_USER)], new \DateTime('2010-06-01')));
    }
}
