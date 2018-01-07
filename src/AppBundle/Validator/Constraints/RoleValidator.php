<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class RoleValidator extends ConstraintValidator
{

    protected $allowedRoles = ['ROLE_CUSTOMER', 'ROLE_USER', 'ROLE_TEAMLEAD', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];

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
