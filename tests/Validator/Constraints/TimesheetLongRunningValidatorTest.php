<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Configuration\ConfigLoaderInterface;
use App\Configuration\SystemConfiguration;
use App\Entity\Timesheet;
use App\Validator\Constraints\TimesheetLongRunning;
use App\Validator\Constraints\TimesheetLongRunningValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\TimesheetLongRunning
 * @covers \App\Validator\Constraints\TimesheetLongRunningValidator
 */
class TimesheetLongRunningValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return $this->createMyValidator(120);
    }

    protected function createMyValidator(int $minutes)
    {
        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = new SystemConfiguration($loader, [
            'timesheet' => [
                'rules' => [
                    'long_running_duration' => $minutes,
                ],
            ],
        ]);

        return new TimesheetLongRunningValidator($config);
    }

    public function testConstraintIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testInvalidValueThrowsException()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new NotBlank(), new TimesheetLongRunning(['message' => 'myMessage']));
    }

    public function testLongRunningTriggers()
    {
        $begin = new \DateTime();
        $end = new \DateTime('+10 hour');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);
        $timesheet->setEnd($end);

        $this->validator->validate($timesheet, new TimesheetLongRunning());

        $this->buildViolation('Maximum duration of {{ value }} hours exceeded.')
            ->atPath('property.path.duration')
            ->setParameter('{{ value }}', '02:00')
            ->setCode(TimesheetLongRunning::LONG_RUNNING)
            ->assertRaised();
    }

    public function testLongRunningTriggersOverMaximum()
    {
        $timesheet = new Timesheet();
        $timesheet->setBegin(new \DateTime());
        $timesheet->setEnd(new \DateTime());
        $timesheet->setDuration(31536060);

        $this->validator->validate($timesheet, new TimesheetLongRunning());

        $this->buildViolation('Maximum duration exceeded.')
            ->atPath('property.path.duration')
            ->setCode(TimesheetLongRunning::MAXIMUM)
            ->assertRaised();
    }

    public function testLongRunningDoesNotTriggerOnMaximum()
    {
        $timesheet = new Timesheet();
        $timesheet->setBegin(new \DateTime());
        $timesheet->setEnd(new \DateTime());
        $timesheet->setDuration(31536000);

        $this->validator->validate($timesheet, new TimesheetLongRunning());

        $this->assertNoViolation();
    }

    public function testLongRunningNotTriggersIfConfiguredToZero()
    {
        $this->validator = $this->createMyValidator(0);
        $this->validator->initialize($this->context);

        $begin = new \DateTime();
        $end = new \DateTime('+10 hour');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);
        $timesheet->setEnd($end);

        $this->validator->validate($timesheet, new TimesheetLongRunning());

        $this->assertNoViolation();
    }

    public function testLongRunningNotTriggersIfDurationIsLowerThan()
    {
        $this->validator = $this->createMyValidator(121);
        $this->validator->initialize($this->context);

        $begin = new \DateTime();
        $end = new \DateTime('+2 hour');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);
        $timesheet->setEnd($end);

        $this->validator->validate($timesheet, new TimesheetLongRunning());

        $this->assertNoViolation();
    }

    public function testNotTriggersOnRunningRecord()
    {
        $begin = new \DateTime('-10 hour');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);

        $this->validator->validate($timesheet, new TimesheetLongRunning());
        $this->assertNoViolation();
    }

    public function testGetTargets()
    {
        $constraint = new TimesheetLongRunning();
        self::assertEquals('class', $constraint->getTargets());
    }
}
