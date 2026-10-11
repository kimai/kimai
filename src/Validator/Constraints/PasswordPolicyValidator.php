<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Configuration\SystemConfiguration;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Constraints\PasswordStrengthValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class PasswordPolicyValidator extends ConstraintValidator
{
    public function __construct(private readonly SystemConfiguration $configuration)
    {
    }

    public function validate(#[\SensitiveParameter] mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof PasswordPolicy)) {
            throw new UnexpectedTypeException($constraint, PasswordPolicy::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        $minScore = $this->configuration->getPasswordStrength();

        if ($minScore < PasswordStrength::STRENGTH_WEAK) {
            return;
        }

        $validator = new PasswordStrengthValidator();
        $validator->initialize($this->context);
        $validator->validate($value, new PasswordStrength(minScore: min($minScore, PasswordStrength::STRENGTH_VERY_STRONG)));
    }
}
