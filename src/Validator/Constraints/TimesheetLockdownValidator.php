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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetLockdownValidator extends ConstraintValidator
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $auth;
    /**
     * @var TimesheetConfiguration
     */
    private $configuration;

    public function __construct(AuthorizationCheckerInterface $auth, TimesheetConfiguration $configuration)
    {
        $this->auth = $auth;
        $this->configuration = $configuration;
    }

    /**
     * @param TimesheetEntity $timesheet
     * @param Constraint $constraint
     */
    public function validate($timesheet, Constraint $constraint)
    {
        if (!($constraint instanceof TimesheetLockdown)) {
            throw new UnexpectedTypeException($constraint, TimesheetLockdown::class);
        }

        if (!\is_object($timesheet) || !($timesheet instanceof TimesheetEntity)) {
            return;
        }

        $timesheetStart = $timesheet->getBegin();

        if (null === $timesheetStart) {
            return;
        }

        $lockedStart = $this->configuration->getLockdownPeriodStart();
        if (empty($lockedStart)) {
            return;
        }

        $lockedEnd = $this->configuration->getLockdownPeriodEnd();
        if (empty($lockedEnd)) {
            return;
        }

        $gracePeriod = $this->configuration->getLockdownGracePeriod();
        if (!empty($gracePeriod)) {
            $gracePeriod = $gracePeriod . ' ';
        }

        try {
            $lockdownStart = new \DateTime($lockedStart, $timesheetStart->getTimezone());
            $lockdownEnd = new \DateTime($lockedEnd, $timesheetStart->getTimezone());
            $lockdownGrace = new \DateTime($gracePeriod . $lockdownEnd->format('Y-m-d'), $timesheetStart->getTimezone());
        } catch (\Exception $ex) {
            // should not happen, but ... if parsing of datetimes fails: skip validation
            return;
        }

        // misconfiguration
        if ($lockdownEnd < $lockdownStart) {
            return;
        }

        // validate only entries added before the end of lockdown period
        if ($timesheetStart > $lockdownEnd) {
            return;
        }

        // lockdown never takes effect for users with special permission
        if ($this->auth->isGranted('lockdown_override_timesheet')) {
            return;
        }

        if (!empty($constraint->now)) {
            if ($constraint->now instanceof \DateTime) {
                $now = $constraint->now;
            } else {
                try {
                    $now = new \DateTime($constraint->now, $timesheetStart->getTimezone());
                } catch (\Exception $ex) {
                }
            }
        }

        if (empty($now)) {
            $now = new \DateTime('now', $timesheetStart->getTimezone());
        }

        // further validate entries inside of the most recent lockdown
        if ($timesheetStart > $lockdownStart && $timesheetStart < $lockdownEnd) {
            // if grace period is still in effect, validation succeeds
            if ($now < $lockdownGrace) {
                return;
            }

            // if user has special role, validation succeeds
            if ($this->auth->isGranted('lockdown_grace_timesheet')) {
                return;
            }
        }

        // raise a violation for all entries before the start of lockdown period
        $this->context->buildViolation('Please change begin/end, as this timesheet is in a locked period.')
            ->atPath('begin')
            ->setTranslationDomain('validators')
            ->setCode(TimesheetLockdown::PERIOD_LOCKED)
            ->addViolation();
    }
}
