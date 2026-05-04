<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Configuration\SystemConfiguration;
use App\Form\DataTransformer\WebhookEndpointsJsonTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * System-Configuration is a JSON string — not an array.
 * Therefor two conversions are needed:
 *
 * 1. On read: the string reaches PRE_SET_DATA *before* model transformers
 *    run. CollectionType's ResizeFormListener throws on a non-iterable at that stage.
 *    We decode via a high-priority listener so the resize listener sees an array.
 *
 * 2. On submit: the model transformer's reverseTransform() encodes the
 *    array back to a JSON string that Configuration::setValue() will accept.
 */
final class WebhookEndpointsType extends AbstractType
{
    public function __construct(private readonly SystemConfiguration $systemConfiguration)
    {
    }

    public function getParent(): string
    {
        return CollectionType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Decode existing configuration, which is a JSON  (System configuration only handles strings)
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event): void {
                $data = $event->getData();
                if (!\is_string($data)) {
                    return;
                }
                if (trim($data) === '' || trim($data) === '[]') {
                    $event->setData([]);

                    return;
                }
                try {
                    $decoded = json_decode($data, true, 16, \JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    $event->setData([]);

                    return;
                }
                $event->setData(\is_array($decoded) ? $decoded : []);
            },
            1000
        );

        $builder->addModelTransformer(new WebhookEndpointsJsonTransformer());
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['max_endpoints'] = $this->systemConfiguration->getMaxWebhookEndpoints();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entry_type' => WebhookEndpointType::class,
            'entry_options' => ['label' => false],
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'prototype' => true,
            'prototype_name' => '__name__',
            'required' => false,
            'label' => 'webhook.endpoints',
            'translation_domain' => 'system-configuration',
            'constraints' => [
                new Callback([$this, 'validateEndpoints']),
            ],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'webhook_endpoints';
    }

    /**
     * @param mixed $value
     */
    public function validateEndpoints($value, ExecutionContextInterface $context): void
    {
        if (!\is_array($value)) {
            return;
        }

        $max = $this->systemConfiguration->getMaxWebhookEndpoints();

        if (\count($value) > $max) {
            $context->buildViolation('Too many webhook endpoints: {{ count }} configured, limit is {{ max }}.')
                ->setParameters(['{{ count }}' => (string) \count($value), '{{ max }}' => (string) $max])
                ->addViolation();
        }

        $seenUrls = [];
        foreach ($value as $index => $row) {
            if (!\is_array($row)) {
                continue;
            }
            $url = \is_string($row['url'] ?? null) ? trim($row['url']) : '';
            if ($url !== '' && isset($seenUrls[$url])) {
                $context->buildViolation('Duplicate endpoint URL: {{ url }}')
                    ->atPath((string) $index . '.url')
                    ->setParameter('{{ url }}', $url)
                    ->addViolation();
            }
            if ($url !== '') {
                $seenUrls[$url] = true;
            }
        }
    }
}
