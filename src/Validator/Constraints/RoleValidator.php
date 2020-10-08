<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Security\RoleService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class RoleValidator extends ConstraintValidator
{
    /**
     * @var RoleService
     */
    private $service;

    public function __construct(RoleService $service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Role) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\Role');
        }

        $roles = $value;

        if (!\is_array($roles)) {
            $roles = [$roles];
        }

        // the fos user entity uppercases the roles by default
        $allowedRoles = array_map('strtoupper', $this->service->getAvailableNames());

        foreach ($roles as $role) {
            if (!\is_string($role) || !\in_array($role, $allowedRoles)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($role))
                    ->setCode(Role::ROLE_ERROR)
                    ->addViolation();
            }
        }
    }
}
