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

        $found = [];
        foreach ($constraint->characters as $character) {
            if (str_contains($value, $character)) {
                $found[] = $character;
            }
        }

        if (count($found) > 0) {
            $this->context->buildViolation(NoSpecialCharacters::getErrorName(NoSpecialCharacters::SPECIAL_CHARACTERS_FOUND))
                ->setTranslationDomain('validators')
                ->setParameter('{{ chars }}', implode(' ', $constraint->characters))
                ->setCode(NoSpecialCharacters::SPECIAL_CHARACTERS_FOUND)
                ->addViolation();
        }
    }
}
