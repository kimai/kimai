<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Form\DataTransformer\EntityByIdTransformer;
use App\Repository\CustomerRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

final class CustomerByIdType extends AbstractType
{
    public function __construct(private readonly CustomerRepository $repository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new EntityByIdTransformer($this->repository));
    }

    public function getParent(): string
    {
        return CustomerType::class;
    }
}
