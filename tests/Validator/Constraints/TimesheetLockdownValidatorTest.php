<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Configuration\ConfigLoaderInterface;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Timesheet\LockdownService;
use App\Validator\Constraints\TimesheetLockdown;
use App\Validator\Constraints\TimesheetLockdownValidator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\TimesheetLockdown
 * @covers \App\Validator\Constraints\TimesheetLockdownValidator
 * @extends ConstraintValidatorTestCase<TimesheetLockdownValidator>
 */
class TimesheetLockdownValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): TimesheetLockdownValidator
    {
        return $this->createMyValidator(false, false, null, null, null);
    }

    protected function createMyValidator(bool $allowOverwriteFull, bool $allowOverwriteGrace, ?string $start, ?string $end, ?string $grace): TimesheetLockdownValidator
    {
        $auth = $this->createMock(Security::class);
        $auth->method('getUser')->willReturn(new User());
        $auth->method('isGranted')->willReturnCallback(
            function ($attributes, $subject = null) use ($allowOverwriteFull, $allowOverwriteGrace) {
                switch ($attributes) {
                    case 'lockdown_override_timesheet':
                        return $allowOverwriteFull;
                    case 'lockdown_grace_timesheet':
                        return $allowOverwriteGrace;
                }

                return false;
            }
        );

        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = SystemConfigurationFactory::create($loader, [
            'timesheet' => [
                'rules' => [
                    'lockdown_period_start' => $start,
                    'lockdown_period_end' => $end,
                    'lockdown_grace_period' => $grace,
                ],
            ]
        ]);

        return new TimesheetLockdownValidator($auth, new LockdownService($config));
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testInvalidValueThrowsException(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new NotBlank(), new TimesheetLockdown(['message' => 'myMessage']));
    }

    public function testValidatorWithoutNowConstraint(): void
    {
        $this->validator = $this->createMyValidator(false, false, 'first day of last month', 'last day of last month', '+10 days');
        $this->validator->initialize($this->context);

        $begin = new \DateTime('first day of last month');
        $begin->modify('-5 days');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);

        $constraint = new TimesheetLockdown(['message' => 'myMessage']);

        $this->validator->validate($timesheet, $constraint);

        $this->buildViolation('This period is locked, please choose a later date.')
            ->atPath('property.path.begin_date')
            ->setCode(TimesheetLockdown::PERIOD_LOCKED)
            ->assertRaised();
    }

    public function testValidatorWithEmptyTimesheet(): void
    {
        $this->validator = $this->createMyValidator(false, false, 'first day of last month', 'last day of last month', '+10 days');
        $this->validator->initialize($this->context);

        $constraint = new TimesheetLockdown(['message' => 'myMessage']);

        $this->validator->validate(new Timesheet(), $constraint);
        self::assertEmpty($this->context->getViolations());
    }

    public function testValidatorWithoutNowStringConstraint(): void
    {
        $this->validator = $this->createMyValidator(false, false, 'first day of last month', 'last day of last month', '+10 days');
        $this->validator->initialize($this->context);

        $begin = new \DateTime('first day of last month');
        $begin->modify('+5 days');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);

        $constraint = new TimesheetLockdown(['message' => 'myMessage', 'now' => 'first day of this month']);

        $this->validator->validate($timesheet, $constraint);
        self::assertEmpty($this->context->getViolations());
    }

    public function testValidatorWithEndBeforeStartPeriod(): void
    {
        $this->validator = $this->createMyValidator(false, false, 'first day of this month', 'last day of last month', '+10 days');
        $this->validator->initialize($this->context);

        $begin = new \DateTime('first day of last month');
        $begin->modify('+5 days');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);

        $constraint = new TimesheetLockdown(['message' => 'myMessage', 'now' => 'first day of this month']);

        $this->validator->validate($timesheet, $constraint);
        self::assertEmpty($this->context->getViolations());
    }

    /**
     * @dataProvider getTestData
     */
    public function testLockdown(bool $allowOverwriteFull, bool $allowOverwriteGrace, string $beginModifier, string $nowModifier, bool $isViolation): void
    {
        $this->validator = $this->createMyValidator($allowOverwriteFull, $allowOverwriteGrace, 'first day of last month', 'last day of last month', '+10 days');
        $this->validator->initialize($this->context);

        $begin = new \DateTime('first day of last month');
        $begin->modify($beginModifier);
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);

        $now = new \DateTime('first day of this month');
        $now->modify($nowModifier);

        $constraint = new TimesheetLockdown(['message' => 'myMessage', 'now' => $now]);

        $this->validator->validate($timesheet, $constraint);

        if ($isViolation) {
            $this->buildViolation('This period is locked, please choose a later date.')
                ->atPath('property.path.begin_date')
                ->setCode(TimesheetLockdown::PERIOD_LOCKED)
                ->assertRaised();
        } else {
            self::assertEmpty($this->context->getViolations());
        }
    }

    public static function getTestData()
    {
        // changing before last dockdown period is not allowed
        yield [false, false, '-5 days', '+5 days', true];
        // changing before last dockdown period is not allowed with grace permission
        yield [false, true, '-5 days', '+5 days', true];
        // changing before last dockdown period is allowed with full permission
        yield [true, true, '-5 days', '+5 days', false];
        yield [true, false, '-5 days', '+5 days', false];
        // changing a value in the last lockdown period is allowed during grace period
        yield [false, false, '+5 days', '+5 days', false];
        // changing outside grace period is not allowed
        yield [false, false, '+5 days', '+11 days', true];
        // changing outside grace period is allowed with grace and full permission
        yield [false, true, '+5 days', '+11 days', false];
        yield [true, false, '+5 days', '+11 days', false];
        yield [true, true, '+5 days', '+11 days', false];
    }

    /**
     * @dataProvider getConfigTestData
     */
    public function testLockdownConfig(bool $allowOverwriteFull, bool $allowOverwriteGrace, ?string $lockdownBegin, ?string $lockdownEnd, ?string $grace, bool $isViolation): void
    {
        $this->validator = $this->createMyValidator($allowOverwriteFull, $allowOverwriteGrace, $lockdownBegin, $lockdownEnd, $grace);
        $this->validator->initialize($this->context);

        $begin = new \DateTime('first day of last month');
        $begin->modify('+5 days');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);

        $now = new \DateTime('first day of this month');

        $constraint = new TimesheetLockdown(['message' => 'myMessage', 'now' => $now]);

        $this->validator->validate($timesheet, $constraint);

        if ($isViolation) {
            $this->buildViolation('This period is locked, please choose a later date.')
                ->atPath('property.path.begin')
                ->setCode(TimesheetLockdown::PERIOD_LOCKED)
                ->assertRaised();
        } else {
            self::assertEmpty($this->context->getViolations());
        }
    }

    public static function getConfigTestData()
    {
        yield [false, false, null, null, null, false];
        yield [false, false, '+5 days', null, null, false];
        yield [false, false, null, '+5 days', null, false];

        yield [false, true, 'öööö', '+11 days', null, false];
        yield [false, true, '+5 days', '+5 of !!!!', null, false];
    }
}
