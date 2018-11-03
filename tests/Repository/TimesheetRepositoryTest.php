<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Repository\Query\BaseQuery;
use App\Repository\Query\TimesheetQuery;
use App\Repository\RepositoryException;
use App\Tests\DataFixtures\TimesheetFixtures;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

/**
 * @covers \App\Repository\TimesheetRepository
 */
class TimesheetRepositoryTest extends AbstractRepositoryTest
{
    public function testResultTypeForQueryState()
    {
        $em = $this->getEntityManager();
        $repository = $em->getRepository(Timesheet::class);

        $query = new TimesheetQuery();

        $result = $repository->findByQuery($query);
        $this->assertInstanceOf(Pagerfanta::class, $result);

        $query->setResultType(BaseQuery::RESULT_TYPE_QUERYBUILDER);
        $result = $repository->findByQuery($query);
        $this->assertInstanceOf(QueryBuilder::class, $result);

        $query->setResultType(BaseQuery::RESULT_TYPE_PAGER);
        $result = $repository->findByQuery($query);
        $this->assertInstanceOf(Pagerfanta::class, $result);

        $query->setResultType(BaseQuery::RESULT_TYPE_OBJECTS);
        $result = $repository->findByQuery($query);
        $this->assertInternalType('array', $result);
    }

    public function testStoppedEntriesCannotBeStoppedAgain()
    {
        $em = $this->getEntityManager();
        $user = $this->getUserByRole($em, User::ROLE_USER);
        $repository = $em->getRepository(Timesheet::class);

        $fixtures = new TimesheetFixtures();
        $fixtures->setUser($user);
        $fixtures->setAmount(1);

        $this->importFixture($em, $fixtures);

        $query = new TimesheetQuery();
        $query->setResultType(BaseQuery::RESULT_TYPE_OBJECTS);
        $query->setUser($user);
        $query->setState(TimesheetQuery::STATE_STOPPED);

        $entities = $repository->findByQuery($query);

        $this->assertCount(1, $entities);
        $this->assertInstanceOf(Timesheet::class, $entities[0]);

        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage('Timesheet entry already stopped');

        $repository->stopRecording($entities[0]);
    }

    public function testStopRecording()
    {
        $em = $this->getEntityManager();
        $user = $this->getUserByRole($em, User::ROLE_USER);
        $repository = $em->getRepository(Timesheet::class);

        $fixtures = new TimesheetFixtures();
        $fixtures->setUser($user);
        $fixtures->setAmountRunning(1);
        $this->importFixture($em, $fixtures);

        $timesheet = $repository->find(1);
        $this->assertInstanceOf(Timesheet::class, $timesheet);
        $this->assertNull($timesheet->getEnd());

        $result = $repository->stopRecording($timesheet);
        $this->assertTrue($result);
        $this->assertInstanceOf(\DateTime::class, $timesheet->getEnd());
    }
}
