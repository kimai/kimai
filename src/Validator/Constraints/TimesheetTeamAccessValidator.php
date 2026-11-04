<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\Timesheet as TimesheetEntity;
use App\Entity\User;
use App\Security\RolePermissionManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetTeamAccessValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Security $security,
        private readonly RolePermissionManager $permissionManager,
        private readonly ManagerRegistry $registry,
    )
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof TimesheetTeamAccess)) {
            throw new UnexpectedTypeException($constraint, TimesheetTeamAccess::class);
        }

        if (!\is_object($value) || !($value instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($value, TimesheetEntity::class);
        }

        $user = $this->security->getUser();
        if (!($user instanceof User) || $user->canSeeAllData()) {
            return;
        }

        $originalData = $this->getOriginalData($value);

        $project = $value->getProject();
        if ($project !== null && $this->hasAssociationChanged($value, 'project', $project, $originalData)) {
            if (!$this->permissionManager->checkTeamAccessProject($project, $user)) {
                $this->context->buildViolation(TimesheetTeamAccess::getErrorName(TimesheetTeamAccess::PROJECT_ACCESS_ERROR))
                    ->atPath('project')
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetTeamAccess::PROJECT_ACCESS_ERROR)
                    ->addViolation();
            }
        }

        $activity = $value->getActivity();
        if ($activity !== null && $this->hasAssociationChanged($value, 'activity', $activity, $originalData)) {
            if (!$this->permissionManager->checkTeamAccessActivity($activity, $user)) {
                $this->context->buildViolation(TimesheetTeamAccess::getErrorName(TimesheetTeamAccess::ACTIVITY_ACCESS_ERROR))
                    ->atPath('activity')
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetTeamAccess::ACTIVITY_ACCESS_ERROR)
                    ->addViolation();
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getOriginalData(TimesheetEntity $timesheet): array
    {
        if ($timesheet->getId() === null) {
            return [];
        }

        $manager = $this->registry->getManagerForClass(TimesheetEntity::class);
        if (!($manager instanceof EntityManagerInterface)) {
            return [];
        }

        return $manager->getUnitOfWork()->getOriginalEntityData($timesheet);
    }

    /**
     * @param array<string, mixed> $originalData
     */
    private function hasAssociationChanged(TimesheetEntity $timesheet, string $field, Project|Activity $current, array $originalData): bool
    {
        if ($timesheet->getId() === null) {
            return true;
        }

        if (!\array_key_exists($field, $originalData)) {
            return true;
        }

        $original = $originalData[$field];

        if ($original === null) {
            return true;
        }

        if ($original === $current) {
            return false;
        }

        if (!\is_object($original) || !method_exists($original, 'getId')) {
            return true;
        }

        if ($original->getId() === null || $current->getId() === null) {
            return true;
        }

        return $original->getId() !== $current->getId();
    }
}
