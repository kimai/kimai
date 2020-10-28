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

class HexColorValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof HexColor) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\HexColor');
        }

        $color = $value;

        if ($color === null || (\is_string($color) && empty($color))) {
            return;
        }

        if (!\is_string($color) || 1 !== preg_match('/^#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})$/i', $color)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($color))
                ->setCode(HexColor::HEX_COLOR_ERROR)
                ->addViolation();
        }
    }
}
