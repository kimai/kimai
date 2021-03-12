<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator;

use App\Configuration\SystemConfiguration;
use App\Entity\Timesheet;
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\TimesheetRepository;
use App\Timesheet\RateService;
use App\Validator\Constraints\TimesheetBudgetUsedConstraint;
use App\Validator\TimesheetBudgetUsedValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\TimesheetBudgetUsedConstraint
 * @covers \App\Validator\TimesheetBudgetUsedValidator
 */
class TimesheetBudgetUsedValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        $configuration = $this->createMock(SystemConfiguration::class);
        $customerRepository = $this->createMock(CustomerRepository::class);
        $projectRepository = $this->createMock(ProjectRepository::class);
        $activityRepository = $this->createMock(ActivityRepository::class);
        $timesheetRepository = $this->createMock(TimesheetRepository::class);
        $rateService = new RateService([], $timesheetRepository);

        return new TimesheetBudgetUsedValidator($configuration, $customerRepository, $projectRepository, $activityRepository, $timesheetRepository, $rateService);
    }

    public function testConstraintIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testTargetIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new TimesheetBudgetUsedConstraint());
    }

    // FIXME add tests!!!
}
