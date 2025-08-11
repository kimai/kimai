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
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Validator\Constraints\TimesheetFutureTimes;
use App\Validator\Constraints\TimesheetFutureTimesValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<TimesheetFutureTimesValidator>
 */
#[CoversClass(TimesheetFutureTimes::class)]
#[CoversClass(TimesheetFutureTimesValidator::class)]
class TimesheetFutureTimesValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): TimesheetFutureTimesValidator
    {
        return $this->createMyValidator(false);
    }

    protected function createMyValidator(bool $allowFutureTimes = false): TimesheetFutureTimesValidator
    {
        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = SystemConfigurationFactory::create($loader, [
            'timesheet' => [
                'rules' => [
                    'allow_future_times' => $allowFutureTimes,
                ],
                'rounding' => [
                    'default' => [
                        'begin' => 1
                    ]
                ]
            ]
        ]);

        return new TimesheetFutureTimesValidator($config);
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testInvalidValueThrowsException(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new NotBlank(), new TimesheetFutureTimes(['message' => 'myMessage']));
    }

    public function testFutureBeginIsDisallowed(): void
    {
        $begin = new \DateTime('+10 hour');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);

        $this->validator->validate($timesheet, new TimesheetFutureTimes(['message' => 'myMessage']));

        $this->buildViolation('The begin date cannot be in the future.')
            ->atPath('property.path.begin_date')
            ->setCode(TimesheetFutureTimes::BEGIN_IN_FUTURE_ERROR)
            ->assertRaised();
    }

    public function testFutureBeginIsAllowed(): void
    {
        $this->validator = $this->createMyValidator(true);
        $this->validator->initialize($this->context);

        $begin = new \DateTime('+10 hour');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);

        $this->validator->validate($timesheet, new TimesheetFutureTimes(['message' => 'myMessage']));
        self::assertEmpty($this->context->getViolations());
    }
}
