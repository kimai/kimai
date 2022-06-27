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
final class InvoiceRendererType extends AbstractType
{
    private $service;

    public function __construct(ServiceInvoice $service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
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
            'search' => false,
        ]);
    }

    public function getGroupBy(string $value, string $label, string $index): string
    {
        $renderer = $label;

        $parts = explode('.', $renderer);

        if (\count($parts) > 2) {
            array_pop($parts);
        }

        $type = array_pop($parts);

        if (\in_array(strtolower($type), ['json', 'txt', 'xml'])) {
            return 'programmatic';
        }

        return ucfirst($type);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
