<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class NotCurrentPasswordValidator extends ConstraintValidator
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof NotCurrentPassword)) {
            throw new UnexpectedTypeException($constraint, NotCurrentPassword::class);
        }

        if (!($value instanceof User)) {
            return;
        }

        $plainPassword = $value->getPlainPassword();
        $currentPassword = $value->getPassword();

        if ($plainPassword === null || $plainPassword === '' || $currentPassword === null || $currentPassword === '') {
            return;
        }

        // the password hash is only updated after validation, so it still contains the current password
        if ($this->passwordHasher->isPasswordValid($value, $plainPassword)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('plainPassword')
                ->setTranslationDomain('validators')
                ->setCode(NotCurrentPassword::SAME_AS_CURRENT_PASSWORD)
                ->addViolation();
        }
    }
}
