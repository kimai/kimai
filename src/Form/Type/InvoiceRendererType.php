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
    public function __construct(private ServiceInvoice $service)
    {
    }

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
            'label' => 'invoice_renderer',
            'choices' => array_flip($documents),
            'group_by' => [$this, 'getGroupBy'],
            'choice_label' => function ($choiceValue, $key, $value) {
                return match (strtolower($choiceValue)) {
                    'xml' => 'XML',
                    'javascript' => 'JSON',
                    default => $choiceValue,
                };
            },
            'translation_domain' => 'invoice-renderer',
            'search' => false,
        ]);
    }

    public function getGroupBy(string $value, string $label, string $index): string
    {
        $parts = explode('.', $label);

        if (\count($parts) > 2) {
            array_pop($parts);
        }

        $type = array_pop($parts);

        if (!\is_string($type)) {
            return 'programmatic';
        }

        return match (strtolower($type)) {
            'json', 'txt', 'xml' => 'programmatic',
            'docx', 'doc' => 'Word',
            'xls', 'xlsx' => 'Excel',
            'ods' => 'LibreOffice',
            default => strtoupper($type),
        };
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
