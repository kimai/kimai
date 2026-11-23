<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Configuration\SystemConfiguration;
use App\Entity\Timesheet as TimesheetEntity;
use App\Form\Model\MultiUserTimesheet;
use App\Repository\TimesheetRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetOverlappingValidator extends ConstraintValidator
{
    public function __construct(
        private readonly SystemConfiguration $configuration,
        private readonly TimesheetRepository $repository
    )
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof TimesheetOverlapping)) {
            throw new UnexpectedTypeException($constraint, TimesheetOverlapping::class);
        }

        if (!\is_object($value) || !($value instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($value, TimesheetEntity::class);
        }

        if ($value instanceof MultiUserTimesheet) {
            return;
        }

        $begin = $value->getBegin();
        $end = $value->getEnd();

        // this case is handled in TimesheetValidator and should not raise a second validation
        if ($begin !== null && $end !== null && $begin > $end) {
            return;
        }

        if ($this->configuration->isTimesheetAllowOverlappingRecords()) {
            return;
        }

        if (!$this->repository->hasRecordForTime($value)) {
            return;
        }

        $this->context->buildViolation('You already have an entry for this time.')
            ->atPath('begin_date')
            ->setTranslationDomain('validators')
            ->setCode(TimesheetOverlapping::RECORD_OVERLAPPING)
            ->addViolation();
    }
}
