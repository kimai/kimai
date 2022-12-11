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

final class ColorChoicesValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ColorChoices) {
            throw new UnexpectedTypeException($constraint, ColorChoices::class);
        }

        if (!\is_string($value) || trim($value) === '') {
            return;
        }

        $colors = explode(',', $value);

        foreach ($colors as $color) {
            $color = explode('|', $color);
            $name = $color[0];
            $code = $color[0];
            if (\count($color) > 1) {
                $code = $color[1];
            }

            if (empty($name)) {
                $name = $code;
            }

            if (1 !== preg_match('/^#[0-9a-fA-F]{6}$/i', $code)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($code))
                    ->setCode(ColorChoices::COLOR_CHOICES_ERROR)
                    ->addViolation();

                return;
            }

            if ($name === $code) {
                return;
            }

            $name = str_replace(['-', ' '], '', $name);
            $length = mb_strlen($name);

            if ($length > $constraint->maxLength || !preg_match('/^[a-zA-Z0-9]+$/', $name)) {
                $this->context->buildViolation($constraint->invalidNameMessage)
                    ->setParameter('{{ name }}', $this->formatValue($name))
                    ->setParameter('{{ color }}', $this->formatValue($code))
                    ->setParameter('{{ max }}', $this->formatValue($constraint->maxLength))
                    ->setParameter('{{ count }}', $this->formatValue($length))
                    ->setCode(ColorChoices::COLOR_CHOICES_NAME_ERROR)
                    ->addViolation();
            }
        }
    }
}
