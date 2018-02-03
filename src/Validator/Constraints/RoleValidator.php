<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Entity\User;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Class RoleValidator
 */
class RoleValidator extends ConstraintValidator
{

    /**
     * @var string[]
     */
    protected $allowedRoles = [
        User::ROLE_CUSTOMER,
        User::ROLE_USER,
        User::ROLE_TEAMLEAD,
        User::ROLE_ADMIN,
        User::ROLE_SUPER_ADMIN
    ];

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Role) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\Role');
        }

        $roles = $value;

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        foreach ($roles as $role) {
            if (!is_string($role) || !in_array($role, $this->allowedRoles)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($role))
                    ->setCode(Role::ROLE_ERROR)
                    ->addViolation();
            }
        }
    }
}
