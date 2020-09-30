<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Configuration\TimesheetConfiguration;
use App\Entity\Timesheet as TimesheetEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetFutureTimesValidator extends ConstraintValidator
{
    /**
     * @var TimesheetConfiguration
     */
    private $configuration;

    public function __construct(TimesheetConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param TimesheetEntity $timesheet
     * @param Constraint $constraint
     */
    public function validate($timesheet, Constraint $constraint)
    {
        if (!($constraint instanceof TimesheetFutureTimes)) {
            throw new UnexpectedTypeException($constraint, TimesheetFutureTimes::class);
        }

        if (!\is_object($timesheet) || !($timesheet instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($timesheet, TimesheetEntity::class);
        }

        if ($this->configuration->isAllowFutureTimes()) {
            return;
        }

        $now = new \DateTime('now', $timesheet->getBegin()->getTimezone());

        // allow configured default rounding time + 1 minute - see #1295
        $allowedDiff = ($this->configuration->getDefaultRoundingBegin() * 60) + 60;
        if (($now->getTimestamp() + $allowedDiff) < $timesheet->getBegin()->getTimestamp()) {
            $this->context->buildViolation('The begin date cannot be in the future.')
                ->atPath('begin')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetFutureTimes::BEGIN_IN_FUTURE_ERROR)
                ->addViolation();
        }
    }
}
