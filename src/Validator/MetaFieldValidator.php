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
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates meta-field values outside the form layer (e.g. in the API),
 * by running all constraints registered for the meta-field.
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
        // the entity is used as validation root, so constraints can access it via the execution context
        $violations = $this->validator->validate($entity, new Callback(
            function (mixed $entity, ExecutionContextInterface $context) use ($metaField, $value): void {
                $context
                    ->getValidator()
                    ->inContext($context)
                    ->atPath((string) $metaField->getName())
                    ->validate($value, $metaField->getConstraints());
            }
        ));

        if ($violations->count() > 0) {
            throw new ValidationFailedException($violations);
        }
    }
}
