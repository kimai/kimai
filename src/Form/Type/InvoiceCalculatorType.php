<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Invoice\ServiceInvoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select an invoice calculator.
 */
final class InvoiceCalculatorType extends AbstractType
{
    public function __construct(private ServiceInvoice $service)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $renderer = [];
        foreach ($this->service->getCalculator() as $calculator) {
            $renderer[$calculator->getId()] = $calculator->getId();
        }

        $resolver->setDefaults([
            'label' => 'invoice_calculator',
            'choices' => $renderer,
            'choice_label' => function ($renderer) {
                return $renderer;
            },
            'translation_domain' => 'invoice-calculator',
            'search' => false,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
