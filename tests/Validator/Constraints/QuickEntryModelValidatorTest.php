<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Model\QuickEntryModel as QuickEntryModelEntity;
use App\Validator\Constraints\QuickEntryModel;
use App\Validator\Constraints\QuickEntryModelValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\QuickEntryModel
 * @covers \App\Validator\Constraints\QuickEntryModelValidator
 * @extends ConstraintValidatorTestCase<QuickEntryModelValidator>
 */
class QuickEntryModelValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): QuickEntryModelValidator
    {
        return new QuickEntryModelValidator();
    }

    public function testConstraintIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testInvalidValueThrowsException()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new QuickEntryModel());
    }

    public function testTriggersOnMissingProjectAndActivity()
    {
        $model = new QuickEntryModelEntity();
        $timesheet = new Timesheet();
        $timesheet->setBegin(new \DateTime());
        $timesheet->setBegin(new \DateTime('+ 1 hour'));
        $model->addTimesheet($timesheet);

        $this->validator->validate($model, new QuickEntryModel());

        $this->buildViolation('An activity needs to be selected.')
            ->atPath('property.path.activity')
            ->setCode(QuickEntryModel::ACTIVITY_REQUIRED)
            ->buildNextViolation('A project needs to be selected.')
            ->atPath('property.path.project')
            ->setCode(QuickEntryModel::PROJECT_REQUIRED)
            ->assertRaised();
    }

    public function testTriggersOnMissingActivity()
    {
        $model = new QuickEntryModelEntity();
        $model->setProject(new Project());
        $timesheet = new Timesheet();
        $timesheet->setBegin(new \DateTime());
        $timesheet->setBegin(new \DateTime('+ 1 hour'));
        $model->addTimesheet($timesheet);

        $this->validator->validate($model, new QuickEntryModel());

        $this->buildViolation('An activity needs to be selected.')
            ->atPath('property.path.activity')
            ->setCode(QuickEntryModel::ACTIVITY_REQUIRED)
            ->assertRaised();
    }

    public function testTriggersOnMissingProject()
    {
        $model = new QuickEntryModelEntity();
        $model->setActivity(new Activity());
        $timesheet = new Timesheet();
        $timesheet->setBegin(new \DateTime());
        $timesheet->setBegin(new \DateTime('+ 1 hour'));
        $model->addTimesheet($timesheet);

        $this->validator->validate($model, new QuickEntryModel());

        $this->buildViolation('A project needs to be selected.')
            ->atPath('property.path.project')
            ->setCode(QuickEntryModel::PROJECT_REQUIRED)
            ->assertRaised();
    }

    public function testDoesNotTriggerOnPrototype()
    {
        $model = new QuickEntryModelEntity();

        $this->validator->validate($model, new QuickEntryModel());

        $this->assertNoViolation();
    }

    public function testDoesNotTriggerOnProperlyFilled()
    {
        $model = new QuickEntryModelEntity();
        $model->setActivity(new Activity());
        $model->setProject(new Project());
        $timesheet = new Timesheet();
        $timesheet->setBegin(new \DateTime());
        $timesheet->setBegin(new \DateTime('+ 1 hour'));
        $model->addTimesheet($timesheet);

        $this->validator->validate($model, new QuickEntryModel());

        $this->assertNoViolation();
    }
}
