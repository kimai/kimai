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

final class ExportRendererValidator extends ConstraintValidator
{
    /**
     * @param string|mixed $value
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof ExportRenderer)) {
            throw new UnexpectedTypeException($constraint, ExportRenderer::class);
        }

        if ($value === null) {
            return;
        }

        $ids = ['csv', 'xlsx', 'pdf'];

        if (!\is_string($value) || !\in_array($value, $ids, true)) {
            $this->context->buildViolation(ExportRenderer::getErrorName(ExportRenderer::UNKNOWN_TYPE))
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setTranslationDomain('validators')
                ->setCode(ExportRenderer::UNKNOWN_TYPE)
                ->addViolation();
        }
    }
}
