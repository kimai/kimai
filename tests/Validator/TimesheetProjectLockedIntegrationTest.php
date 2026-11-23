<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Validator\Constraints\QuickEntryTimesheet;
use App\Validator\Constraints\Timesheet as TimesheetConstraint;
use App\Validator\Constraints\TimesheetProjectLocked;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Makes sure that the project lock is part of the tagged timesheet constraint chain,
 * which is what the timesheet forms, the API and the quick-entry week view use.
 */
#[Group('integration')]
class TimesheetProjectLockedIntegrationTest extends KernelTestCase
{
    private function getValidator(): ValidatorInterface
    {
        self::bootKernel();

        $validator = self::getContainer()->get(ValidatorInterface::class);
        self::assertInstanceOf(ValidatorInterface::class, $validator);

        return $validator;
    }

    private function createTimesheet(?string $lockedUntil, string $begin): Timesheet
    {
        $customer = new Customer('a customer');
        $customer->setTimezone('Europe/Berlin');

        $project = new Project();
        $project->setName('a project');
        $project->setCustomer($customer);
        if ($lockedUntil !== null) {
            $project->setLockedUntil(new \DateTimeImmutable($lockedUntil));
        }

        $activity = new Activity();
        $activity->setName('an activity');
        $activity->setProject($project);

        $timesheet = new Timesheet();
        $timesheet->setUser(new User());
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);
        $timesheet->setBegin(new \DateTime($begin));
        $timesheet->setEnd(new \DateTime($begin . ' +1 hour'));

        return $timesheet;
    }

    /**
     * @return array<string>
     */
    private function getViolationCodes(Timesheet $timesheet, Constraint $constraint): array
    {
        $codes = [];

        /** @var ConstraintViolationInterface $violation */
        foreach ($this->getValidator()->validate($timesheet, $constraint) as $violation) {
            $code = $violation->getCode();
            if ($code !== null) {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    public function testTimesheetConstraintChainContainsProjectLock(): void
    {
        $timesheet = $this->createTimesheet('2020-06-30 23:59:59', '2020-06-15 10:00:00');

        self::assertContains(
            TimesheetProjectLocked::PERIOD_LOCKED,
            $this->getViolationCodes($timesheet, new TimesheetConstraint())
        );
    }

    public function testQuickEntryConstraintChainContainsProjectLock(): void
    {
        $timesheet = $this->createTimesheet('2020-06-30 23:59:59', '2020-06-15 10:00:00');
        $timesheet->setDuration(3600);

        self::assertContains(
            TimesheetProjectLocked::PERIOD_LOCKED,
            $this->getViolationCodes($timesheet, new QuickEntryTimesheet())
        );
    }

    public function testUnlockedProjectRaisesNoLockViolation(): void
    {
        $timesheet = $this->createTimesheet(null, '2020-06-15 10:00:00');

        self::assertNotContains(
            TimesheetProjectLocked::PERIOD_LOCKED,
            $this->getViolationCodes($timesheet, new TimesheetConstraint())
        );
    }

    public function testTimesheetAfterLockedPeriodRaisesNoLockViolation(): void
    {
        $timesheet = $this->createTimesheet('2020-06-30 23:59:59', '2020-07-01 10:00:00');

        self::assertNotContains(
            TimesheetProjectLocked::PERIOD_LOCKED,
            $this->getViolationCodes($timesheet, new TimesheetConstraint())
        );
    }
}
