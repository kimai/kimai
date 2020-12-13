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

class AllowedHtmlTagsValidator extends ConstraintValidator
{
    /**
     * @param string|mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!($constraint instanceof AllowedHtmlTags)) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\AllowedHtmlTags');
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = (string) $value;

        if (strip_tags($value, $constraint->tags) !== $value) {
            $this->context->buildViolation('This string contains invalid HTML tags.')
                ->setTranslationDomain('validators')
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(AllowedHtmlTags::DISALLOWED_TAGS_FOUND)
                ->addViolation();
        }
    }
}
