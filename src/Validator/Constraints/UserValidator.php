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
            $this->validateEmailExists($user->getId(), $user->getEmail(), 'email', User::USER_EXISTING_EMAIL, $context);
            $this->validateUsernameExists($user->getId(), $user->getEmail(), 'email', User::USER_EXISTING_EMAIL_AS_NAME, $context);
        }

        if ($user->getUsername() !== null) {
            $this->validateEmailExists($user->getId(), $user->getUsername(), 'username', User::USER_EXISTING_NAME_AS_EMAIL, $context);
            $this->validateUsernameExists($user->getId(), $user->getUsername(), 'username', User::USER_EXISTING_NAME, $context);
        }
    }

    private function validateEmailExists(?int $userId, string $email, string $path, string $code, ExecutionContextInterface $context): void
    {
        $existingByEmail = $this->userService->findUserByEmail($email);

        if (null !== $existingByEmail && $userId !== $existingByEmail->getId()) {
            $context->buildViolation(User::getErrorName($code))
                ->atPath($path)
                ->setTranslationDomain('validators')
                ->setCode($code)
                ->addViolation();
        }
    }

    private function validateUsernameExists(?int $userId, string $username, string $path, string $code, ExecutionContextInterface $context): void
    {
        $existingByName = $this->userService->findUserByName($username);

        if (null !== $existingByName && $userId !== $existingByName->getId()) {
            $context->buildViolation(User::getErrorName($code))
                ->atPath($path)
                ->setTranslationDomain('validators')
                ->setCode($code)
                ->addViolation();
        }
    }
}
