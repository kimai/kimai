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

class DateTimeFormatValidator extends ConstraintValidator
{
    /**
     * @param string|mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!($constraint instanceof DateTimeFormat)) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\DateTimeFormat');
        }

        $valid = true;

        try {
            $test = new \DateTime($value);
        } catch (\Exception $ex) {
            $valid = false;
        }

        if (false === $valid) {
            $this->context->buildViolation('The given value is not a valid datetime format.')
                ->setTranslationDomain('validators')
                ->setCode(DateTimeFormat::INVALID_FORMAT)
                ->addViolation();
        }
    }
}
