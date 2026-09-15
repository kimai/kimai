<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Model\QuickEntryModel;
use App\Model\QuickEntryWeek as QuickEntryWeekModel;
use App\Repository\TimesheetRepository;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Validator\Constraints\QuickEntryWeek;
use App\Validator\Constraints\QuickEntryWeekValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends ConstraintValidatorTestCase<QuickEntryWeekValidator>
 */
#[CoversClass(QuickEntryWeek::class)]
#[CoversClass(QuickEntryWeekValidator::class)]
class QuickEntryWeekValidatorTest extends ConstraintValidatorTestCase
{
    private TimesheetRepository&\PHPUnit\Framework\MockObject\MockObject $repository;
    private bool $allowOverlappingRecords = false;

    protected function createConstraint(): QuickEntryWeek
    {
        return new QuickEntryWeek();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        $configuration = SystemConfigurationFactory::createStub([
            'timesheet' => [
                'default_begin' => '09:00',
                'rules' => [
                    'allow_overlapping_records' => $this->allowOverlappingRecords,
                ],
            ],
        ]);

        $this->repository = $this->createMock(TimesheetRepository::class);

        return new QuickEntryWeekValidator($configuration, $this->repository);
    }

    private function createUser(int $id): User
    {
        $user = new User();
        $user->setTimezone('Europe/Berlin');

        $reflection = new \ReflectionProperty(User::class, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($user, $id);

        return $user;
    }

    private function newTimesheet(User $user, \DateTime $begin, int $durationSeconds): Timesheet
    {
        $timesheet = new Timesheet();
        $timesheet->setUser($user);
        $timesheet->setBegin(clone $begin);
        $timesheet->setDuration($durationSeconds);

        return $timesheet;
    }

    public function testInvalidValueThrowsException(): void
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), $this->createConstraint());
    }

    public function testChainsNewRowsOnTheSameDayBackToBack(): void
    {
        $user = $this->createUser(1);
        $this->repository->method('findForDay')->willReturn([]);

        $week = new QuickEntryWeekModel(new \DateTime('2026-08-17'));

        $begin = new \DateTime('2026-08-19 09:00:00', new \DateTimeZone('Europe/Berlin'));

        $projectA = new Project();
        $projectA->setName('Project A');
        $projectB = new Project();
        $projectB->setName('Project B');

        $rowA = new QuickEntryModel($user, $projectA);
        $timesheetA = $this->newTimesheet($user, $begin, 2 * 3600);
        $rowA->addTimesheet($timesheetA);

        $rowB = new QuickEntryModel($user, $projectB);
        $timesheetB = $this->newTimesheet($user, $begin, 3 * 3600);
        $rowB->addTimesheet($timesheetB);

        $week->setRows([$rowA, $rowB]);

        $this->validator->validate($week, $this->createConstraint());

        $this->assertNoViolation();

        self::assertSame('09:00', $timesheetA->getBegin()?->format('H:i'));
        self::assertSame('11:00', $timesheetA->getEnd()?->format('H:i'));
        self::assertSame('11:00', $timesheetB->getBegin()?->format('H:i'));
        self::assertSame('14:00', $timesheetB->getEnd()?->format('H:i'));
    }

    public function testNewRowChainsAfterWorkAlreadyRecordedOutsideTheGrid(): void
    {
        $user = $this->createUser(1);

        $week = new QuickEntryWeekModel(new \DateTime('2026-08-17'));

        $begin = new \DateTime('2026-08-19 09:00:00', new \DateTimeZone('Europe/Berlin'));

        $existing = $this->newTimesheet($user, $begin, 3600);
        $existing->setEnd((clone $begin)->modify('+1 hour'));
        $reflection = new \ReflectionProperty(Timesheet::class, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($existing, 99);

        $this->repository->method('findForDay')->willReturn([$existing]);

        $project = new Project();
        $project->setName('Project A');

        $row = new QuickEntryModel($user, $project);
        $newTimesheet = $this->newTimesheet($user, $begin, 2 * 3600);
        $row->addTimesheet($newTimesheet);

        $week->setRows([$row]);

        $this->validator->validate($week, $this->createConstraint());

        $this->assertNoViolation();

        self::assertSame('10:00', $newTimesheet->getBegin()?->format('H:i'));
        self::assertSame('12:00', $newTimesheet->getEnd()?->format('H:i'));
    }

    public function testThreeTenHourRowsOnTheSameDayAreRejectedNamingTheDay(): void
    {
        $user = $this->createUser(1);
        $this->repository->method('findForDay')->willReturn([]);

        $week = new QuickEntryWeekModel(new \DateTime('2026-08-17'));
        $begin = new \DateTime('2026-08-19 00:00:00', new \DateTimeZone('Europe/Berlin'));

        $rows = [];
        foreach (['Project A', 'Project B', 'Project C'] as $name) {
            $project = new Project();
            $project->setName($name);
            $row = new QuickEntryModel($user, $project);
            $row->addTimesheet($this->newTimesheet($user, $begin, 10 * 3600));
            $rows[] = $row;
        }

        $week->setRows($rows);

        $this->validator->validate($week, $this->createConstraint());

        $this->buildViolation('The entries for {{ day }} exceed the hours available on that day.')
            ->setParameter('{{ day }}', '2026-08-19')
            ->atPath('property.path.rows[2].timesheets[0].duration')
            ->setCode(QuickEntryWeek::DAY_CAPACITY_EXCEEDED)
            ->assertRaised();
    }

    public function testDaySumBoundaryExactly24HoursIsAcceptedAnd24Hours1MinuteIsRejected(): void
    {
        $user = $this->createUser(1);
        $this->repository->method('findForDay')->willReturn([]);
        $this->useDefaultBeginOfMidnight();

        $begin = new \DateTime('2026-08-19 00:00:00', new \DateTimeZone('Europe/Berlin'));
        $project = new Project();
        $project->setName('Project A');

        $week = new QuickEntryWeekModel(new \DateTime('2026-08-17'));
        $row = new QuickEntryModel($user, $project);
        $row->addTimesheet($this->newTimesheet($user, $begin, 24 * 3600));
        $week->setRows([$row]);

        $this->validator->validate($week, $this->createConstraint());
        $this->assertNoViolation();
    }

    public function testDaySumBoundaryOneMinuteOverIsRejected(): void
    {
        $user = $this->createUser(1);
        $this->repository->method('findForDay')->willReturn([]);
        $this->useDefaultBeginOfMidnight();

        $begin = new \DateTime('2026-08-19 00:00:00', new \DateTimeZone('Europe/Berlin'));
        $project = new Project();
        $project->setName('Project A');

        $week = new QuickEntryWeekModel(new \DateTime('2026-08-17'));
        $row = new QuickEntryModel($user, $project);
        $row->addTimesheet($this->newTimesheet($user, $begin, 24 * 3600 + 60));
        $week->setRows([$row]);

        $this->validator->validate($week, $this->createConstraint());

        $this->buildViolation('The entries for {{ day }} exceed the hours available on that day.')
            ->setParameter('{{ day }}', '2026-08-19')
            ->atPath('property.path.rows[0].timesheets[0].duration')
            ->setCode(QuickEntryWeek::DAY_CAPACITY_EXCEEDED)
            ->assertRaised();
    }

    public function testSingleCellOf30HoursIsRejected(): void
    {
        $user = $this->createUser(1);
        $this->repository->method('findForDay')->willReturn([]);

        $begin = new \DateTime('2026-08-19 09:00:00', new \DateTimeZone('Europe/Berlin'));
        $project = new Project();
        $project->setName('Project A');

        $week = new QuickEntryWeekModel(new \DateTime('2026-08-17'));
        $row = new QuickEntryModel($user, $project);
        $row->addTimesheet($this->newTimesheet($user, $begin, 30 * 3600));
        $week->setRows([$row]);

        $this->validator->validate($week, $this->createConstraint());

        $this->buildViolation('The entries for {{ day }} exceed the hours available on that day.')
            ->setParameter('{{ day }}', '2026-08-19')
            ->atPath('property.path.rows[0].timesheets[0].duration')
            ->setCode(QuickEntryWeek::DAY_CAPACITY_EXCEEDED)
            ->assertRaised();
    }

    private function useDefaultBeginOfMidnight(): void
    {
        $configuration = SystemConfigurationFactory::createStub([
            'timesheet' => [
                'default_begin' => '00:00',
                'rules' => ['allow_overlapping_records' => false],
            ],
        ]);
        $this->validator = new QuickEntryWeekValidator($configuration, $this->repository);
        $this->validator->initialize($this->context);
    }

    public function testDstSpringForwardDayAllows23HoursAndRejects23Hours1Minute(): void
    {
        // 2026-03-29 is when Europe/Berlin loses an hour (23h calendar day)
        $user = $this->createUser(1);
        $this->repository->method('findForDay')->willReturn([]);
        $this->useDefaultBeginOfMidnight();

        $begin = new \DateTime('2026-03-29 00:00:00', new \DateTimeZone('Europe/Berlin'));
        $project = new Project();
        $project->setName('Project A');

        $week = new QuickEntryWeekModel(new \DateTime('2026-03-29'));
        $row = new QuickEntryModel($user, $project);
        $row->addTimesheet($this->newTimesheet($user, $begin, 23 * 3600));
        $week->setRows([$row]);

        $this->validator->validate($week, $this->createConstraint());
        $this->assertNoViolation();
    }

    public function testDstSpringForwardDayRejects23Hours1Minute(): void
    {
        $user = $this->createUser(1);
        $this->repository->method('findForDay')->willReturn([]);
        $this->useDefaultBeginOfMidnight();

        $begin = new \DateTime('2026-03-29 00:00:00', new \DateTimeZone('Europe/Berlin'));
        $project = new Project();
        $project->setName('Project A');

        $week = new QuickEntryWeekModel(new \DateTime('2026-03-29'));
        $row = new QuickEntryModel($user, $project);
        $row->addTimesheet($this->newTimesheet($user, $begin, 23 * 3600 + 60));
        $week->setRows([$row]);

        $this->validator->validate($week, $this->createConstraint());

        $this->buildViolation('The entries for {{ day }} exceed the hours available on that day.')
            ->setParameter('{{ day }}', '2026-03-29')
            ->atPath('property.path.rows[0].timesheets[0].duration')
            ->setCode(QuickEntryWeek::DAY_CAPACITY_EXCEEDED)
            ->assertRaised();
    }

    public function testDstFallBackDayAllows25HoursAndRejects25Hours1Minute(): void
    {
        // 2026-10-25 is when Europe/Berlin gains an hour (25h calendar day)
        $user = $this->createUser(1);
        $this->repository->method('findForDay')->willReturn([]);
        $this->useDefaultBeginOfMidnight();

        $begin = new \DateTime('2026-10-25 00:00:00', new \DateTimeZone('Europe/Berlin'));
        $project = new Project();
        $project->setName('Project A');

        $week = new QuickEntryWeekModel(new \DateTime('2026-10-25'));
        $row = new QuickEntryModel($user, $project);
        $row->addTimesheet($this->newTimesheet($user, $begin, 25 * 3600));
        $week->setRows([$row]);

        $this->validator->validate($week, $this->createConstraint());
        $this->assertNoViolation();
    }

    public function testDstFallBackDayRejects25Hours1Minute(): void
    {
        $user = $this->createUser(1);
        $this->repository->method('findForDay')->willReturn([]);
        $this->useDefaultBeginOfMidnight();

        $begin = new \DateTime('2026-10-25 00:00:00', new \DateTimeZone('Europe/Berlin'));
        $project = new Project();
        $project->setName('Project A');

        $week = new QuickEntryWeekModel(new \DateTime('2026-10-25'));
        $row = new QuickEntryModel($user, $project);
        $row->addTimesheet($this->newTimesheet($user, $begin, 25 * 3600 + 60));
        $week->setRows([$row]);

        $this->validator->validate($week, $this->createConstraint());

        $this->buildViolation('The entries for {{ day }} exceed the hours available on that day.')
            ->setParameter('{{ day }}', '2026-10-25')
            ->atPath('property.path.rows[0].timesheets[0].duration')
            ->setCode(QuickEntryWeek::DAY_CAPACITY_EXCEEDED)
            ->assertRaised();
    }

    public function testRunningTimerOnTheDayBlocksAndRejectsNewRowsWithTheOverlapMessage(): void
    {
        $user = $this->createUser(1);

        $begin = new \DateTime('2026-08-19 09:00:00', new \DateTimeZone('Europe/Berlin'));

        $runningExternal = $this->newTimesheet($user, $begin, 0);
        $runningExternal->setEnd(null);
        $reflection = new \ReflectionProperty(Timesheet::class, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($runningExternal, 99);

        $this->repository->method('findForDay')->willReturn([$runningExternal]);

        $project = new Project();
        $project->setName('Project A');

        $week = new QuickEntryWeekModel(new \DateTime('2026-08-17'));
        $row = new QuickEntryModel($user, $project);
        $newTimesheet = $this->newTimesheet($user, $begin, 2 * 3600);
        $row->addTimesheet($newTimesheet);
        $week->setRows([$row]);

        $this->validator->validate($week, $this->createConstraint());

        $this->buildViolation('You already have an entry for this time.')
            ->atPath('property.path.rows[0].timesheets[0].duration')
            ->setCode(QuickEntryWeek::RECORD_OVERLAPPING)
            ->assertRaised();
    }

    public function testRunningTimerThatBeganTheDayBeforeStillBlocksNewRowsWithTheOverlapMessage(): void
    {
        $user = $this->createUser(1);

        // findForDay is the repository boundary — the day-before-and-still-running case is
        // its own responsibility (TimesheetRepositoryTest::testFindForDayAlsoReturnsARunningEntryThatBeganTheDayBefore);
        // here we prove the validator treats whatever findForDay returns as blocking, matching that contract.
        $overnightRunning = $this->newTimesheet($user, new \DateTime('2026-08-18 23:00:00', new \DateTimeZone('Europe/Berlin')), 0);
        $overnightRunning->setEnd(null);
        $ref = new \ReflectionProperty(Timesheet::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($overnightRunning, 42);

        $this->repository->method('findForDay')->willReturn([$overnightRunning]);

        $project = new Project();
        $project->setName('Project A');

        $begin = new \DateTime('2026-08-19 09:00:00', new \DateTimeZone('Europe/Berlin'));
        $week = new QuickEntryWeekModel(new \DateTime('2026-08-17'));
        $row = new QuickEntryModel($user, $project);
        $newTimesheet = $this->newTimesheet($user, $begin, 2 * 3600);
        $row->addTimesheet($newTimesheet);
        $week->setRows([$row]);

        $this->validator->validate($week, $this->createConstraint());

        $this->buildViolation('You already have an entry for this time.')
            ->atPath('property.path.rows[0].timesheets[0].duration')
            ->setCode(QuickEntryWeek::RECORD_OVERLAPPING)
            ->assertRaised();
    }

    public function testAllowOverlappingRecordsSkipsChainingAndOverlapRejection(): void
    {
        $this->allowOverlappingRecords = true;
        $this->createValidator();
        $user = $this->createUser(1);
        $this->repository->method('findForDay')->willReturn([]);

        $begin = new \DateTime('2026-08-19 09:00:00', new \DateTimeZone('Europe/Berlin'));

        $projectA = new Project();
        $projectA->setName('Project A');
        $projectB = new Project();
        $projectB->setName('Project B');

        $rowA = new QuickEntryModel($user, $projectA);
        $timesheetA = $this->newTimesheet($user, $begin, 2 * 3600);
        $rowA->addTimesheet($timesheetA);

        $rowB = new QuickEntryModel($user, $projectB);
        $timesheetB = $this->newTimesheet($user, $begin, 3 * 3600);
        $rowB->addTimesheet($timesheetB);

        $week = new QuickEntryWeekModel(new \DateTime('2026-08-17'));
        $week->setRows([$rowA, $rowB]);

        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);
        $this->validator->validate($week, $this->createConstraint());

        $this->assertNoViolation();

        // pre-fix behaviour: both keep their prefilled default begin time, still overlapping
        self::assertSame('09:00', $timesheetA->getBegin()?->format('H:i'));
        self::assertSame('09:00', $timesheetB->getBegin()?->format('H:i'));
    }

    public function testChainingPastMidnightIsRejectedByTheDayCapacityRuleEvenUnderTheTotalHourSum(): void
    {
        // default begin 20:00 + 16h of entered work would chain past midnight
        // (36000s = 10h total, well under the 24h day-length, but 20:00 + 16h crosses 00:00)
        $user = $this->createUser(1);
        $this->repository->method('findForDay')->willReturn([]);

        $configuration = SystemConfigurationFactory::createStub([
            'timesheet' => [
                'default_begin' => '20:00',
                'rules' => ['allow_overlapping_records' => false],
            ],
        ]);
        $this->validator = new QuickEntryWeekValidator($configuration, $this->repository);
        $this->validator->initialize($this->context);

        $begin = new \DateTime('2026-08-19 20:00:00', new \DateTimeZone('Europe/Berlin'));
        $project = new Project();
        $project->setName('Project A');

        $week = new QuickEntryWeekModel(new \DateTime('2026-08-17'));
        $row = new QuickEntryModel($user, $project);
        $row->addTimesheet($this->newTimesheet($user, $begin, 16 * 3600));
        $week->setRows([$row]);

        $this->validator->validate($week, $this->createConstraint());

        $this->buildViolation('The entries for {{ day }} exceed the hours available on that day.')
            ->setParameter('{{ day }}', '2026-08-19')
            ->atPath('property.path.rows[0].timesheets[0].duration')
            ->setCode(QuickEntryWeek::DAY_CAPACITY_EXCEEDED)
            ->assertRaised();
    }

    public function testDayCapacityViolationIsTranslatedInTheValidatorsDomain(): void
    {
        // ConstraintValidatorTestCase's own translator stub returns the message id verbatim
        // regardless of domain, so it cannot catch a wrong/missing translation domain. Wire a
        // real ExecutionContext with a translator mock that asserts the domain argument instead.
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with(self::anything(), self::anything(), 'validators')
            ->willReturn('translated');

        $validatorStub = $this->createStub(ValidatorInterface::class);
        $context = new ExecutionContext($validatorStub, 'root', $translator);
        $context->setNode('InvalidValue', null, null, 'property.path');
        $context->setConstraint($this->createConstraint());

        $user = $this->createUser(1);
        $this->repository->method('findForDay')->willReturn([]);
        $this->useDefaultBeginOfMidnight();
        $this->validator->initialize($context);

        $begin = new \DateTime('2026-08-19 00:00:00', new \DateTimeZone('Europe/Berlin'));
        $project = new Project();
        $project->setName('Project A');

        $week = new QuickEntryWeekModel(new \DateTime('2026-08-17'));
        $row = new QuickEntryModel($user, $project);
        $row->addTimesheet($this->newTimesheet($user, $begin, 24 * 3600 + 60));
        $week->setRows([$row]);

        $this->validator->validate($week, $this->createConstraint());
    }

    public function testAnExistingOverlappingPairIsToleratedAndDoesNotBlockANewRow(): void
    {
        $user = $this->createUser(1);

        $begin = new \DateTime('2026-08-19 09:00:00', new \DateTimeZone('Europe/Berlin'));

        // two existing (already persisted) entries that already overlap each other
        $existingA = $this->newTimesheet($user, $begin, 3600);
        $existingA->setEnd((clone $begin)->modify('+1 hour'));
        $ref = new \ReflectionProperty(Timesheet::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($existingA, 10);

        $existingB = $this->newTimesheet($user, (clone $begin)->modify('+30 minutes'), 3600);
        $existingB->setEnd((clone $begin)->modify('+90 minutes'));
        $ref->setValue($existingB, 11);

        $this->repository->method('findForDay')->willReturn([$existingA, $existingB]);

        $project = new Project();
        $project->setName('Project A');

        // both existing rows are resubmitted unchanged (as the whole grid always does),
        // plus one genuinely new row for the same day
        $rowA = new QuickEntryModel($user, $project);
        $rowA->addTimesheet($existingA);
        $rowB = new QuickEntryModel($user, $project);
        $rowB->addTimesheet($existingB);
        $rowC = new QuickEntryModel($user, $project);
        $newTimesheet = $this->newTimesheet($user, $begin, 3600);
        $rowC->addTimesheet($newTimesheet);

        $week = new QuickEntryWeekModel(new \DateTime('2026-08-17'));
        $week->setRows([$rowA, $rowB, $rowC]);

        $this->validator->validate($week, $this->createConstraint());

        $this->assertNoViolation();

        // the existing pair is left exactly as it was
        self::assertSame('09:00', $existingA->getBegin()?->format('H:i'));
        self::assertSame('09:30', $existingB->getBegin()?->format('H:i'));
        // the new row is chained after the latest existing end (10:30)
        self::assertSame('10:30', $newTimesheet->getBegin()?->format('H:i'));
    }

    public function testClearingADurationDoesNotShiftUnrelatedNewRows(): void
    {
        $user = $this->createUser(1);
        $this->repository->method('findForDay')->willReturn([]);

        $begin = new \DateTime('2026-08-19 09:00:00', new \DateTimeZone('Europe/Berlin'));

        $projectA = new Project();
        $projectA->setName('Project A');
        $projectB = new Project();
        $projectB->setName('Project B');

        // row A had its duration cleared (empty duration on a new cell = nothing entered)
        $rowA = new QuickEntryModel($user, $projectA);
        $clearedTimesheet = $this->newTimesheet($user, $begin, 0);
        $clearedTimesheet->setDuration(null);
        $rowA->addTimesheet($clearedTimesheet);

        $rowB = new QuickEntryModel($user, $projectB);
        $newTimesheet = $this->newTimesheet($user, $begin, 2 * 3600);
        $rowB->addTimesheet($newTimesheet);

        $week = new QuickEntryWeekModel(new \DateTime('2026-08-17'));
        $week->setRows([$rowA, $rowB]);

        $this->validator->validate($week, $this->createConstraint());

        $this->assertNoViolation();

        // row B still starts at the configured default begin, unaffected by the cleared row
        self::assertSame('09:00', $newTimesheet->getBegin()?->format('H:i'));
    }

    public function testTwoDifferentUsersOnTheSameDayAreValidatedIndependently(): void
    {
        $userOne = $this->createUser(1);
        $userTwo = $this->createUser(2);
        $this->repository->method('findForDay')->willReturn([]);

        $begin = new \DateTime('2026-08-19 09:00:00', new \DateTimeZone('Europe/Berlin'));

        $projectOne = new Project();
        $projectOne->setName('Project A');
        $projectTwo = new Project();
        $projectTwo->setName('Project A');

        $rowOne = new QuickEntryModel($userOne, $projectOne);
        $timesheetOne = $this->newTimesheet($userOne, $begin, 2 * 3600);
        $rowOne->addTimesheet($timesheetOne);

        $rowTwo = new QuickEntryModel($userTwo, $projectTwo);
        $timesheetTwo = $this->newTimesheet($userTwo, $begin, 3 * 3600);
        $rowTwo->addTimesheet($timesheetTwo);

        $week = new QuickEntryWeekModel(new \DateTime('2026-08-17'));
        $week->setRows([$rowOne, $rowTwo]);

        $this->validator->validate($week, $this->createConstraint());

        $this->assertNoViolation();

        // each user's own day starts fresh at the default begin, independent of the other user
        self::assertSame('09:00', $timesheetOne->getBegin()?->format('H:i'));
        self::assertSame('09:00', $timesheetTwo->getBegin()?->format('H:i'));
        self::assertSame($userOne, $timesheetOne->getUser());
        self::assertSame($userTwo, $timesheetTwo->getUser());
    }
}
