<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Configuration\LocaleService;
use App\Entity\Timesheet as TimesheetEntity;
use App\Utils\LocaleFormatter;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Prevents that timesheets are booked into a locked project period.
 *
 * Changing or deleting existing records of a locked period is prevented by the TimesheetVoter,
 * as those permissions are checked before the record is handed over to the validator.
 */
final class TimesheetProjectLockedValidator extends ConstraintValidator
{
    public function __construct(private readonly LocaleService $localeService)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof TimesheetProjectLocked)) {
            throw new UnexpectedTypeException($constraint, TimesheetProjectLocked::class);
        }

        if (!\is_object($value) || !($value instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($value, TimesheetEntity::class);
        }

        $project = $value->getProject();
        if ($project === null) {
            return;
        }

        $begin = $value->getBegin();
        if ($begin === null) {
            return;
        }

        if (!$project->isLockedAtDate($begin)) {
            return;
        }

        $formatter = new LocaleFormatter($this->localeService, $value->getUser()?->getLocale() ?? 'en');

        $this->context->buildViolation(TimesheetProjectLocked::getErrorName(TimesheetProjectLocked::PERIOD_LOCKED))
            ->atPath('begin_date')
            ->setTranslationDomain('validators')
            ->setParameter('%date%', $formatter->dateShort($project->getLockedUntil()) ?? '')
            ->setCode(TimesheetProjectLocked::PERIOD_LOCKED)
            ->addViolation();
    }
}
