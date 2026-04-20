<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Configuration\SystemConfiguration;
use App\Form\DataTransformer\JsonEndpointsTransformer;
use App\Webhook\WebhookService;
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
 * Multi-endpoint webhook editor. Extends CollectionType so the UI rendering
 * (prototype, add/remove) comes for free, but the backing value in
 * Kimai's `kimai2_configuration` is a JSON string — not an array. Two
 * conversions are needed:
 *
 * 1. On read: the string reaches PRE_SET_DATA *before* model transformers
 *    run (Symfony Form.php:287 dispatches PRE_SET_DATA with raw model
 *    data, line 300 then runs `modelToNorm`). CollectionType's
 *    ResizeFormListener throws on a non-iterable at that stage. We decode
 *    via a high-priority PRE_SET_DATA listener so the resize listener
 *    sees an array.
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
        // Decode JSON string → array BEFORE CollectionType's resize listener
        // (which throws on non-iterable model data). PRE_SET_DATA runs with
        // raw model data, so transformers alone don't suffice — see Form.php
        // line 287-301 in symfony/form 6.4.
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

        // Preserve secrets on re-save: an empty submitted secret is treated as
        // "no change" for that endpoint. Primary match is by URL; if the user
        // renamed the URL of an existing row the URL key misses, so we fall
        // back to matching by row index (positional). This covers "admin
        // corrects a typo in the URL, didn't touch the secret" — the secret
        // from the row at the same index is restored.
        //
        // An endpoint with an empty secret is a valid configuration (unsigned
        // dispatch), so we never invent a secret — we only preserve one that
        // already existed in the stored blob.
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event): void {
                $submitted = $event->getData();
                if (!\is_array($submitted)) {
                    return;
                }
                $existing = $event->getForm()->getData();
                if (!\is_array($existing) || $existing === []) {
                    return;
                }
                $byUrl = [];
                $byIndex = [];
                foreach ($existing as $i => $row) {
                    if (!\is_array($row)) {
                        continue;
                    }
                    $secret = \is_string($row['secret'] ?? null) ? $row['secret'] : '';
                    if ($secret === '') {
                        continue;
                    }
                    $byIndex[$i] = $secret;
                    if (\is_string($row['url'] ?? null) && $row['url'] !== '') {
                        $byUrl[$row['url']] = $secret;
                    }
                }
                foreach ($submitted as $i => $row) {
                    if (!\is_array($row)) {
                        continue;
                    }
                    $url = trim((string) ($row['url'] ?? ''));
                    $secret = (string) ($row['secret'] ?? '');
                    if ($url === '' || $secret !== '') {
                        continue;
                    }
                    if (isset($byUrl[$url])) {
                        $submitted[$i]['secret'] = $byUrl[$url];
                    } elseif (isset($byIndex[$i])) {
                        $submitted[$i]['secret'] = $byIndex[$i];
                    }
                }
                $event->setData($submitted);
            },
            1000
        );

        $builder->addModelTransformer(new JsonEndpointsTransformer());
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $max = $this->systemConfiguration->find('webhook.max_endpoints');
        $view->vars['max_endpoints'] = \is_int($max) ? $max : WebhookService::DEFAULT_MAX_ENDPOINTS;
        $view->vars['entity_types'] = WebhookEndpointType::ENTITY_TYPES;
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

        $max = $this->systemConfiguration->find('webhook.max_endpoints');
        $max = \is_int($max) ? $max : WebhookService::DEFAULT_MAX_ENDPOINTS;

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
