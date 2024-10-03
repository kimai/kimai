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

final class TimeFormatValidator extends ConstraintValidator
{
    /**
     * @param string|mixed $value
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof TimeFormat)) {
            throw new UnexpectedTypeException($constraint, TimeFormat::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = (string) $value;

        if (preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])$/', $value) !== 1) {
            $this->context->buildViolation('The given value is not a valid time.')
                ->setTranslationDomain('validators')
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(TimeFormat::INVALID_FORMAT)
                ->addViolation();
        }
    }
}
