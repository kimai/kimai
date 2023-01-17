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
use App\Repository\TimesheetRepository;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Validator\Constraints\TimesheetOverlapping;
use App\Validator\Constraints\TimesheetOverlappingValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\TimesheetOverlapping
 * @covers \App\Validator\Constraints\TimesheetOverlappingValidator
 * @extends ConstraintValidatorTestCase<TimesheetOverlappingValidator>
 */
class TimesheetOverlappingValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): TimesheetOverlappingValidator
    {
        return $this->createMyValidator(false, true);
    }

    protected function createMyValidator(bool $allowOverlappingRecords = false, bool $hasRecords = true): TimesheetOverlappingValidator
    {
        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = SystemConfigurationFactory::create($loader, [
            'timesheet' => [
                'rules' => [
                    'allow_overlapping_records' => $allowOverlappingRecords,
                ],
            ],
        ]);
        $repository = $this->createMock(TimesheetRepository::class);
        $repository->method('hasRecordForTime')->willReturn($hasRecords);

        return new TimesheetOverlappingValidator($config, $repository);
    }

    public function testConstraintIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testInvalidValueThrowsException()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new NotBlank(), new TimesheetOverlapping(['message' => 'myMessage'])); // @phpstan-ignore-line
    }

    public function testOverlappingDisallowedWithRecords()
    {
        $begin = new \DateTime();
        $end = new \DateTime('+10 hour');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);
        $timesheet->setEnd($end);

        $this->validator->validate($timesheet, new TimesheetOverlapping(['message' => 'myMessage']));

        $this->buildViolation('You already have an entry for this time.')
            ->atPath('property.path.begin_date')
            ->setCode(TimesheetOverlapping::RECORD_OVERLAPPING)
            ->assertRaised();
    }

    public function testOverlappingDisallowedWithoutRecords()
    {
        $this->validator = $this->createMyValidator(false, false);
        $this->validator->initialize($this->context);

        $begin = new \DateTime();
        $end = new \DateTime('+10 hour');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);
        $timesheet->setEnd($end);

        $this->validator->validate($timesheet, new TimesheetOverlapping(['message' => 'myMessage']));
        self::assertEmpty($this->context->getViolations());
    }

    public function testOverlappingAllowedWithRecords()
    {
        $this->validator = $this->createMyValidator(true, true);
        $this->validator->initialize($this->context);

        $begin = new \DateTime();
        $end = new \DateTime('+10 hour');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);
        $timesheet->setEnd($end);

        $this->validator->validate($timesheet, new TimesheetOverlapping(['message' => 'myMessage']));
        self::assertEmpty($this->context->getViolations());
    }

    public function testOverlappingAllowedWithoutRecords()
    {
        $this->validator = $this->createMyValidator(true, false);
        $this->validator->initialize($this->context);

        $begin = new \DateTime();
        $end = new \DateTime('+10 hour');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);
        $timesheet->setEnd($end);

        $this->validator->validate($timesheet, new TimesheetOverlapping(['message' => 'myMessage']));
        self::assertEmpty($this->context->getViolations());
    }
}
