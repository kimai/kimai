<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
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
 * Custom form field type to select an invoice number generator.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class InvoiceNumberGeneratorType extends AbstractType
{
    /**
     * @var ServiceInvoice
     */
    protected $service;

    /**
     * InvoiceNumberGeneratorType constructor.
     * @param ServiceInvoice $service
     */
    public function __construct(ServiceInvoice $service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $renderer = [];
        foreach ($this->service->getNumberGenerator() as $name => $class) {
            $renderer[$name] = $name;
        }

        $resolver->setDefaults([
            'label' => 'label.invoice_number_generator',
            'choices' => $renderer,
            'choice_label' => function($renderer) {
                return 'invoice_number_generator.' . $renderer;
            }
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
