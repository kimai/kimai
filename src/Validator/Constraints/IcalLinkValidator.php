<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Calendar\IcsValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class IcalLinkValidator extends ConstraintValidator
{
    public function __construct(private IcsValidator $icsValidator)
    {
    }

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
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->setCode(IcalLink::INVALID_URL)
                ->addViolation();
            return;
        }

        // Optionally validate ICS content (only if the URL is accessible)
        try {
            $icsContent = $this->icsValidator->fetchAndValidateIcs($value);
            if ($icsContent === null) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $value)
                    ->setCode(IcalLink::INVALID_ICS)
                    ->addViolation();
            }
        } catch (\Exception $e) {
            // If we can't fetch the content, we'll still allow the URL
            // as it might be a valid URL that's temporarily unavailable
        }
    }
} 