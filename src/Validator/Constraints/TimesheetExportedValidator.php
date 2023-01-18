<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Entity\Timesheet as TimesheetEntity;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetExportedValidator extends ConstraintValidator
{
    public function __construct(private Security $security)
    {
    }

    /**
     * @param TimesheetEntity $timesheet
     * @param Constraint $constraint
     */
    public function validate(mixed $timesheet, Constraint $constraint): void
    {
        if (!($constraint instanceof TimesheetExported)) {
            throw new UnexpectedTypeException($constraint, TimesheetExported::class);
        }

        if (!\is_object($timesheet) || !($timesheet instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($timesheet, TimesheetEntity::class);
        }

        if ($timesheet->getId() === null) {
            return;
        }

        if (!$timesheet->isExported()) {
            return;
        }

        // this was "edit_exported_timesheet" before, but that was wrong, because the first time this
        // can trigger is right when the "export" flag ist set from the "edit form".
        // most teamleads should not have "edit_exported_timesheet" but only "edit_export_other_timesheet"

        if (null !== $this->security->getUser() && $this->security->isGranted('edit_export', $timesheet)) {
            return;
        }

        $this->context->buildViolation(TimesheetExported::getErrorName(TimesheetExported::TIMESHEET_EXPORTED))
            ->atPath('exported')
            ->setTranslationDomain('validators')
            ->setCode(TimesheetExported::TIMESHEET_EXPORTED)
            ->addViolation();
    }
}
