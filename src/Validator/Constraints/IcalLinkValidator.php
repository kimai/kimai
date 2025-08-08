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

class IcalLinkValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof IcalLink) {
            throw new UnexpectedTypeException($constraint, IcalLink::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        // Check if it's a valid URL
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->context->buildViolation($constraint->invalidUrlMessage)
                ->setCode(IcalLink::INVALID_URL)
                ->addViolation();
            return;
        }

        // Check if it ends with .ics
        if (!str_ends_with(strtolower($value), '.ics')) {
            $this->context->buildViolation($constraint->invalidExtensionMessage)
                ->setCode(IcalLink::INVALID_EXTENSION)
                ->addViolation();
        }
    }
} 