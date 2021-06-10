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
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetLongRunningValidator extends ConstraintValidator
{
    private $systemConfiguration;

    public function __construct(SystemConfiguration $systemConfiguration)
    {
        $this->systemConfiguration = $systemConfiguration;
    }

    /**
     * @param TimesheetEntity $timesheet
     * @param Constraint $constraint
     */
    public function validate($timesheet, Constraint $constraint)
    {
        if (!($constraint instanceof TimesheetLongRunning)) {
            throw new UnexpectedTypeException($constraint, TimesheetLongRunning::class);
        }

        if (!\is_object($timesheet) || !($timesheet instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($timesheet, TimesheetEntity::class);
        }

        if ($timesheet->isRunning()) {
            return;
        }

        $maxMinutes = $this->systemConfiguration->getTimesheetLongRunningDuration();

        if ($maxMinutes <= 0) {
            return;
        }

        $duration = $timesheet->getDuration();
        $minutes = (int) $duration / 60;

        if ($minutes < $maxMinutes) {
            return;
        }

        $format = new \App\Utils\Duration();
        $hours = $format->format($maxMinutes * 60);

        // raise a violation for all entries before the start of lockdown period
        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $hours)
            ->setTranslationDomain('validators')
            ->atPath('duration')
            ->setCode(TimesheetLongRunning::LONG_RUNNING)
            ->addViolation();
    }
}
