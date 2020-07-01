<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Configuration\ConfigLoaderInterface;
use App\Configuration\TimesheetConfiguration;
use App\Entity\Timesheet;
use App\Validator\Constraints\TimesheetLockdown;
use App\Validator\Constraints\TimesheetLockdownValidator;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\TimesheetLockdownValidator
 */
class TimesheetLockdownValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return $this->createMyValidator(false, false, null, null, null);
    }

    protected function createMyValidator(bool $allowOverwriteFull, bool $allowOverwriteGrace, ?string $start, ?string $end, ?string $grace)
    {
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
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
        $config = new TimesheetConfiguration($loader, [
            'rules' => [
                'lockdown_period_start' => $start,
                'lockdown_period_end' => $end,
                'lockdown_grace_period' => $grace,
            ],
        ]);

        return new TimesheetLockdownValidator($auth, $config);
    }

    public function testConstraintIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    /**
     * @dataProvider getTestData
     */
    public function testLockdown(bool $allowOverwriteFull, bool $allowOverwriteGrace, string $beginModifier, string $nowModifier, bool $isViolation)
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
            $this->buildViolation('Please change begin/end, as this timesheet is in a locked period.')
                ->atPath('property.path.begin')
                ->setCode(TimesheetLockdown::PERIOD_LOCKED)
                ->assertRaised();
        } else {
            self::assertEmpty($this->context->getViolations());
        }
    }

    public function testValidatorWithoutNowConstraint()
    {
        $this->validator = $this->createMyValidator(false, false, 'first day of last month', 'last day of last month', '+10 days');
        $this->validator->initialize($this->context);

        $begin = new \DateTime('first day of last month');
        $begin->modify('-5 days');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);

        $constraint = new TimesheetLockdown(['message' => 'myMessage']);

        $this->validator->validate($timesheet, $constraint);

        $this->buildViolation('Please change begin/end, as this timesheet is in a locked period.')
            ->atPath('property.path.begin')
            ->setCode(TimesheetLockdown::PERIOD_LOCKED)
            ->assertRaised();
    }

    public function testValidatorWithoutNowStringConstraint()
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

    public function testValidatorWithEndBeforeStartPeriod()
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

    public function getTestData()
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
}
