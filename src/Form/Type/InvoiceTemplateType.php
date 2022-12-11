<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\InvoiceTemplate;
use App\Repository\InvoiceTemplateRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select an invoice template.
 */
final class InvoiceTemplateType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'template',
            'help' => 'help.invoiceTemplate',
            'class' => InvoiceTemplate::class,
            'choice_label' => 'name',
            'query_builder' => function (InvoiceTemplateRepository $repository) {
                return $repository->getQueryBuilderForFormType();
            }
        ]);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
