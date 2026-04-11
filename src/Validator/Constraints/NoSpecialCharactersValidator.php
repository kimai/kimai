<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class NoSpecialCharactersValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof NoSpecialCharacters)) {
            throw new UnexpectedTypeException($constraint, NoSpecialCharacters::class);
        }

        if (!\is_string($value) || $value === '') {
            return;
        }

        if (str_contains($value, '<') // XSS
            || str_contains($value, '>') // XSS
            || str_contains($value, '"') // XSS
            || str_contains($value, '=') // DDE
            // there are many family names that use the ' (like O'Hara), so we cannot forbid them
        ) {
            $this->context->buildViolation(NoSpecialCharacters::getErrorName(NoSpecialCharacters::SPECIAL_CHARACTERS_FOUND))
                ->setTranslationDomain('validators')
                ->setParameter('{{ chars }}', '< " > =')
                ->setCode(NoSpecialCharacters::SPECIAL_CHARACTERS_FOUND)
                ->addViolation();
        }
    }
}
