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
 * Custom form field type to select an invoice renderer.
 */
class InvoiceRendererType extends AbstractType
{
    /**
     * @var ServiceInvoice
     */
    protected $service;

    /**
     * InvoiceRendererType constructor.
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
        foreach ($this->service->getDocuments() as $document) {
            $renderer[$document->getId()] = $document->getId();
        }

        $resolver->setDefaults([
            'label' => 'label.invoice_renderer',
            'choices' => $renderer,
            'choice_label' => function ($renderer) {
                return 'invoice_renderer.' . $renderer;
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
