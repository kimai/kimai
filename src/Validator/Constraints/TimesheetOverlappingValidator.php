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
use App\Repository\TimesheetRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetOverlappingValidator extends ConstraintValidator
{
    /**
     * @var TimesheetConfiguration
     */
    private $configuration;
    /**
     * @var TimesheetRepository
     */
    private $repository;

    public function __construct(TimesheetConfiguration $configuration, TimesheetRepository $repository)
    {
        $this->configuration = $configuration;
        $this->repository = $repository;
    }

    /**
     * @param TimesheetEntity $timesheet
     * @param Constraint $constraint
     */
    public function validate($timesheet, Constraint $constraint)
    {
        if (!($constraint instanceof TimesheetOverlapping)) {
            throw new UnexpectedTypeException($constraint, TimesheetOverlapping::class);
        }

        if (!\is_object($timesheet) || !($timesheet instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($timesheet, TimesheetEntity::class);
        }

        if ($this->configuration->isAllowOverlappingRecords()) {
            return;
        }

        if (!$this->repository->hasRecordForTime($timesheet)) {
            return;
        }

        $this->context->buildViolation('You already have an entry for this time.')
            ->atPath('begin')
            ->setTranslationDomain('validators')
            ->setCode(TimesheetOverlapping::RECORD_OVERLAPPING)
            ->addViolation();
    }
}
