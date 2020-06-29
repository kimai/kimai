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

        try {
            $now = new \DateTime();
            $lockedStart = $this->configuration->getLockdownPeriodStart();
            $lockedEnd = $this->configuration->getLockdownPeriodEnd();
            $gracePeriod = $this->configuration->getLockdownGracePeriod();
        } catch (\Exception $ex) {
            // parsing of datetimes failed, skip validation
            return;
        }

        if (null === $lockedStart || null === $lockedEnd) {
            return;
        }

        // lockdown never takes effect for users with special permission
        if ($this->auth->isGranted('lockdown_complete_override_timesheet')) {
            return;
        }

        // validate only entries added before the end of lockdown period
        if ($timesheetStart < $lockedEnd) {
            // further validate entries inside of the most recent lockdown
            if ($timesheetStart > $lockedStart && $timesheetStart < $lockedEnd) {
                //if grace period is still in effect, validation succeeds
                if (null !== $gracePeriod && $now < $gracePeriod) {
                    return;
                }

                //if user has special role, validation succeeds
                if ($this->auth->isGranted('lockdown_grace_override_timesheet')) {
                    return;
                }
            }

            // otherwise raise a violation
            // this includes all entries before the start of lockdown period
            $this->context->buildViolation('Please change begin/end, as this timesheet is in a locked period.')
                ->atPath('begin')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetLockdown::PERIOD_LOCKED)
                ->addViolation();
        }
    }
}
