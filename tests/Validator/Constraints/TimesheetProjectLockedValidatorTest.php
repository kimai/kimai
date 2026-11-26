<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Configuration\LocaleService;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Validator\Constraints\TimesheetProjectLocked;
use App\Validator\Constraints\TimesheetProjectLockedValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<TimesheetProjectLockedValidator>
 */
#[CoversClass(TimesheetProjectLocked::class)]
#[CoversClass(TimesheetProjectLockedValidator::class)]
class TimesheetProjectLockedValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): TimesheetProjectLockedValidator
    {
        // the message shows the lock date in the format of the timesheet users locale
        return new TimesheetProjectLockedValidator(new LocaleService([
            'en' => array_merge(LocaleService::DEFAULT_SETTINGS, ['date' => 'y-MM-dd']),
            'de' => array_merge(LocaleService::DEFAULT_SETTINGS, ['date' => 'dd.MM.y']),
        ]));
    }

    private function createProject(?string $lockedUntil): Project
    {
        $project = new Project();
        $project->setName('foo');
        $project->setCustomer(new Customer('bar'));

        if ($lockedUntil !== null) {
            $project->setLockedUntil(new \DateTimeImmutable($lockedUntil));
        }

        return $project;
    }

    private function createTimesheet(?Project $project, string $begin): Timesheet
    {
        $timesheet = new Timesheet();
        $timesheet->setBegin(new \DateTime($begin));

        if ($project !== null) {
            $timesheet->setProject($project);
        }

        return $timesheet;
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testInvalidValueThrowsException(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new NotBlank(), new TimesheetProjectLocked());
    }

    public function testTimesheetWithoutProjectIsValid(): void
    {
        $this->validator->validate($this->createTimesheet(null, '2020-06-15 10:00:00'), new TimesheetProjectLocked());

        $this->assertNoViolation();
    }

    public function testTimesheetWithoutBeginIsValid(): void
    {
        $timesheet = new Timesheet();
        $timesheet->setProject($this->createProject('2020-06-15 23:59:59'));

        $this->validator->validate($timesheet, new TimesheetProjectLocked());

        $this->assertNoViolation();
    }

    public function testProjectWithoutLockDateIsValid(): void
    {
        $timesheet = $this->createTimesheet($this->createProject(null), '2020-06-15 10:00:00');

        $this->validator->validate($timesheet, new TimesheetProjectLocked());

        $this->assertNoViolation();
    }

    public function testTimesheetAfterLockedPeriodIsValid(): void
    {
        $timesheet = $this->createTimesheet($this->createProject('2020-06-15 23:59:59'), '2020-06-16 00:00:00');

        $this->validator->validate($timesheet, new TimesheetProjectLocked());

        $this->assertNoViolation();
    }

    public function testTimesheetInsideLockedPeriodIsInvalid(): void
    {
        $timesheet = $this->createTimesheet($this->createProject('2020-06-15 23:59:59'), '2020-05-02 08:00:00');

        $this->validator->validate($timesheet, new TimesheetProjectLocked());

        $this->buildViolation('The project is locked until %date%, please choose a later date.')
            ->atPath('property.path.begin_date')
            ->setParameter('%date%', '2020-06-15')
            ->setCode(TimesheetProjectLocked::PERIOD_LOCKED)
            ->assertRaised();
    }

    public function testTimesheetOnTheLockDateIsInvalid(): void
    {
        // the lock date itself is included in the locked period
        $timesheet = $this->createTimesheet($this->createProject('2020-06-15 23:59:59'), '2020-06-15 23:59:58');

        $this->validator->validate($timesheet, new TimesheetProjectLocked());

        $this->buildViolation('The project is locked until %date%, please choose a later date.')
            ->atPath('property.path.begin_date')
            ->setParameter('%date%', '2020-06-15')
            ->setCode(TimesheetProjectLocked::PERIOD_LOCKED)
            ->assertRaised();
    }

    public function testRunningTimesheetInsideLockedPeriodCannotBeStopped(): void
    {
        // a running record that was started before the lock date cannot be stopped
        $timesheet = $this->createTimesheet($this->createProject('2020-06-15 23:59:59'), '2020-06-15 08:00:00');
        $timesheet->setEnd(new \DateTime('2020-06-20 10:00:00'));

        $this->validator->validate($timesheet, new TimesheetProjectLocked());

        $this->buildViolation('The project is locked until %date%, please choose a later date.')
            ->atPath('property.path.begin_date')
            ->setParameter('%date%', '2020-06-15')
            ->setCode(TimesheetProjectLocked::PERIOD_LOCKED)
            ->assertRaised();
    }

    public function testLockDateIsFormattedWithTheUsersLocale(): void
    {
        $user = new User();
        $user->setLanguage('de');

        $timesheet = $this->createTimesheet($this->createProject('2020-06-15 23:59:59'), '2020-05-02 08:00:00');
        $timesheet->setUser($user);

        $this->validator->validate($timesheet, new TimesheetProjectLocked());

        $this->buildViolation('The project is locked until %date%, please choose a later date.')
            ->atPath('property.path.begin_date')
            ->setParameter('%date%', '15.06.2020')
            ->setCode(TimesheetProjectLocked::PERIOD_LOCKED)
            ->assertRaised();
    }

    public function testErrorNameIsAvailable(): void
    {
        self::assertEquals(
            'The project is locked until %date%, please choose a later date.',
            TimesheetProjectLocked::getErrorName(TimesheetProjectLocked::PERIOD_LOCKED)
        );
    }

    public function testGetTargets(): void
    {
        $sut = new TimesheetProjectLocked();

        self::assertEquals(TimesheetProjectLocked::CLASS_CONSTRAINT, $sut->getTargets());
    }
}
