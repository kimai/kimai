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
        $documents = [];
        foreach ($this->service->getDocuments() as $document) {
            foreach ($this->service->getRenderer() as $renderer) {
                if ($renderer->supports($document)) {
                    $documents[$document->getId()] = $document->getName();
                    break;
                }
            }
        }

        $resolver->setDefaults([
            'label' => 'label.invoice_renderer',
            'choices' => array_flip($documents),
            'group_by' => [$this, 'getGroupBy'],
            'choice_label' => function ($choiceValue, $key, $value) {
                return $choiceValue;
            },
            'translation_domain' => 'invoice-renderer',
            'docu_chapter' => 'invoices.html',
            'search' => false,
        ]);
    }

    /**
     * @param string $value
     * @param string $label
     * @param string $index
     * @return string
     */
    public function getGroupBy($value, $label, $index)
    {
        $renderer = $label;

        $parts = explode('.', $renderer);

        if (\count($parts) > 2) {
            array_pop($parts);
        }

        return ucfirst(array_pop($parts));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
