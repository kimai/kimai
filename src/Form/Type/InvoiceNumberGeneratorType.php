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
 * Custom form field type to select an invoice number generator.
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
        foreach ($this->service->getNumberGenerator() as $generator) {
            $renderer[$generator->getId()] = $generator->getId();
        }

        $resolver->setDefaults([
            'label' => 'label.invoice_number_generator',
            'choices' => $renderer,
            'choice_label' => function ($renderer) {
                return $renderer;
            },
            'translation_domain' => 'invoice-numbergenerator',
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
