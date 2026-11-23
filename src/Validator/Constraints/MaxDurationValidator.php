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
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class MaxDurationValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MaxDuration) {
            throw new UnexpectedTypeException($constraint, MaxDuration::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!\is_int($value)) {
            throw new UnexpectedValueException($value, 'int');
        }

        if ($value <= $constraint->value) {
            return;
        }

        $duration = new \App\Utils\Duration();

        $this->context->buildViolation($constraint->message)
            ->setTranslationDomain('validators')
            ->setParameter('{{ value }}', $duration->format($constraint->value) ?? '')
            ->setCode(MaxDuration::MAX_DURATION_ERROR)
            ->addViolation();
    }
}
