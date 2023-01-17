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
use App\Validator\Constraints\TimesheetZeroDuration;
use App\Validator\Constraints\TimesheetZeroDurationValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\TimesheetZeroDuration
 * @covers \App\Validator\Constraints\TimesheetZeroDurationValidator
 * @extends ConstraintValidatorTestCase<TimesheetZeroDurationValidator>
 */
class TimesheetZeroDurationValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): TimesheetZeroDurationValidator
    {
        return $this->createMyValidator(false);
    }

    protected function createMyValidator(bool $allowZeroDuration = false): TimesheetZeroDurationValidator
    {
        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = SystemConfigurationFactory::create($loader, [
            'timesheet' => [
                'rules' => [
                    'allow_zero_duration' => $allowZeroDuration,
                ],
            ]
        ]);

        return new TimesheetZeroDurationValidator($config);
    }

    public function testConstraintIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testInvalidValueThrowsException()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new NotBlank(), new TimesheetZeroDuration(['message' => 'Duration cannot be zero.'])); // @phpstan-ignore-line
    }

    private function prepareTimesheet()
    {
        // creates Timesheet with same begin and endtime
        $begin = new \DateTime();
        $timesheet = new Timesheet();
        $timesheet->setBegin(clone $begin);
        $timesheet->setEnd(clone $begin);
        $timesheet->setDuration(0);

        return $timesheet;
    }

    public function testZeroDurationIsDisallowed()
    {
        $timesheet = $this->prepareTimesheet();

        $this->validator->validate($timesheet, new TimesheetZeroDuration(['message' => 'Duration cannot be zero.']));

        $this->buildViolation('Duration cannot be zero.')
            ->atPath('property.path.duration')
            ->setCode(TimesheetZeroDuration::ZERO_DURATION_ERROR)
            ->assertRaised();
    }

    public function testZeroDurationIsAllowed()
    {
        $this->validator = $this->createMyValidator(true);
        $this->validator->initialize($this->context);

        $timesheet = $this->prepareTimesheet();

        $this->validator->validate($timesheet, new TimesheetZeroDuration(['message' => 'Duration cannot be zero.']));

        $this->assertNoViolation();
    }
}
