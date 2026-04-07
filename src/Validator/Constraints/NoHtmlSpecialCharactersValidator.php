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

final class NoHtmlSpecialCharactersValidator extends ConstraintValidator
{
    /**
     * @param string|null $value
     * @param Constraint $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof NoHtmlSpecialCharacters)) {
            throw new UnexpectedTypeException($constraint, NoHtmlSpecialCharacters::class);
        }

        if (!\is_string($value)) {
            return;
        }

        if (str_contains($value, '<')
            || str_contains($value, '>')
            || str_contains($value, '"')
            // there are many family names that use the ' (like O'Hara), so we cannot forbid them
        ) {
            $this->context->buildViolation(NoHtmlSpecialCharacters::getErrorName(NoHtmlSpecialCharacters::SPECIAL_CHARACTERS_FOUND))
                ->setTranslationDomain('validators')
                ->setCode(NoHtmlSpecialCharacters::SPECIAL_CHARACTERS_FOUND)
                ->addViolation();
        }
    }
}
