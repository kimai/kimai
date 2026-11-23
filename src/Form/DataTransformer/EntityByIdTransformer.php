<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\DataTransformer;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\DataTransformerInterface;

final class EntityByIdTransformer implements DataTransformerInterface // @phpstan-ignore missingType.generics
{
    public function __construct(private readonly EntityRepository $repository) // @phpstan-ignore missingType.generics
    {
    }

    public function transform(mixed $value): mixed
    {
        if (is_numeric($value)) {
            return $this->repository->find($value);
        }

        return $value;
    }

    public function reverseTransform(mixed $value): mixed
    {
        if (\is_object($value) && method_exists($value, 'getId') && $value->getId() !== null) {
            return (string) $value->getId();
        }

        return $value;
    }
}
