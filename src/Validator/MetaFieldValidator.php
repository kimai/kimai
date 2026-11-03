<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator;

use App\Entity\EntityWithMetaFields;
use App\Entity\MetaTableTypeInterface;
use App\Entity\User;
use App\Entity\UserPreference;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates meta-field and user-preference values outside the form layer (e.g. in the API),
 * by running all constraints registered for the field.
 */
final class MetaFieldValidator
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    /**
     * @throws ValidationFailedException
     */
    public function validate(EntityWithMetaFields $entity, MetaTableTypeInterface $metaField, mixed $value): void
    {
        $this->validateValue($entity, $metaField->getName(), $metaField->getConstraints(), $value);
    }

    /**
     * @throws ValidationFailedException
     */
    public function validateUserPreference(User $user, UserPreference $preference, mixed $value): void
    {
        $this->validateValue($user, $preference->getName(), $preference->getConstraints(), $value);
    }

    /**
     * @param Constraint[] $constraints
     * @throws ValidationFailedException
     */
    private function validateValue(object $root, ?string $name, array $constraints, mixed $value): void
    {
        // like the form layer: empty strings are converted to null before validation,
        // the API receives null as empty string, as the JSON decoder converts all scalar values to strings
        $value = $value === '' ? null : $value;

        // the entity is used as validation root, so constraints can access it via the execution context
        $violations = $this->validator->validate($root, new Callback(
            function (mixed $root, ExecutionContextInterface $context) use ($name, $constraints, $value): void {
                $context
                    ->getValidator()
                    ->inContext($context)
                    ->atPath((string) $name)
                    ->validate($value, $constraints);
            }
        ));

        if ($violations->count() > 0) {
            throw new ValidationFailedException($violations);
        }
    }
}
