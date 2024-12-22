<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Tests\Mocks\TrackingModeServiceFactory;
use App\Validator\Constraints\TimesheetOverlapping;
use App\Validator\Constraints\TimesheetRestart;
use App\Validator\Constraints\TimesheetRestartValidator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\TimesheetRestart
 * @covers \App\Validator\Constraints\TimesheetRestartValidator
 * @extends ConstraintValidatorTestCase<TimesheetRestartValidator>
 */
class TimesheetRestartValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): TimesheetRestartValidator
    {
        return $this->createMyValidator(false, 'default');
    }

    protected function createMyValidator(bool $allowed, string $trackingMode): TimesheetRestartValidator
    {
        $auth = $this->createMock(Security::class);
        $auth->method('getUser')->willReturn(new User());
        $auth->method('isGranted')->willReturn($allowed);

        $service = (new TrackingModeServiceFactory($this))->create($trackingMode);

        return new TimesheetRestartValidator($auth, $service);
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testInvalidValueThrowsException(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new NotBlank(), new TimesheetOverlapping(['message' => 'myMessage']));
    }

    /**
     * @dataProvider getTestData
     */
    public function testRestartDisallowed(bool $allowed, ?string $property, string $trackingMode): void
    {
        $this->validator = $this->createMyValidator($allowed, $trackingMode);
        $this->validator->initialize($this->context);

        $begin = new \DateTime('-10 hour');
        $customer = new Customer('foo');
        $activity = new Activity();
        $project = new Project();
        $project->setCustomer($customer);
        $activity->setProject($project);

        $timesheet = new Timesheet();
        $timesheet
            ->setBegin($begin)
            ->setActivity($activity)
            ->setProject($project)
        ;

        $this->validator->validate($timesheet, new TimesheetRestart(['message' => 'myMessage']));

        if (null !== $property) {
            $this->buildViolation('You are not allowed to start this timesheet record.')
                ->atPath('property.path.' . $property)
                ->setCode(TimesheetRestart::START_DISALLOWED)
                ->assertRaised();
        } else {
            self::assertEmpty($this->context->getViolations());
        }
    }

    public static function getTestData()
    {
        yield [false, 'end_date', 'default'];
        yield [true, null, 'default'];
        yield [false, 'start_date', 'punch'];
    }
}
