<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Entity\User as UserEntity;
use App\User\UserService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UserValidator extends ConstraintValidator
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param UserEntity $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!($constraint instanceof User)) {
            throw new UnexpectedTypeException($constraint, User::class);
        }

        if (!\is_object($value) || !($value instanceof UserEntity)) {
            return;
        }

        $this->validateUser($value, $this->context);
    }

    protected function validateUser(UserEntity $user, ExecutionContextInterface $context)
    {
        if ($user->getEmail() !== null) {
            $existingByEmail = $this->userService->findUserByEmail($user->getEmail());

            if (null !== $existingByEmail && $user->getId() !== $existingByEmail->getId()) {
                $context->buildViolation(User::getErrorName(User::USER_EXISTING_EMAIL))
                    ->atPath('email')
                    ->setTranslationDomain('validators')
                    ->setCode(User::USER_EXISTING_EMAIL)
                    ->addViolation();
            }
        }

        if ($user->getUsername() !== null) {
            $existingByName = $this->userService->findUserByName($user->getUsername());

            if (null !== $existingByName && $user->getId() !== $existingByName->getId()) {
                $context->buildViolation(User::getErrorName(User::USER_EXISTING_NAME))
                    ->atPath('username')
                    ->setTranslationDomain('validators')
                    ->setCode(User::USER_EXISTING_NAME)
                    ->addViolation();
            }
        }
    }
}
