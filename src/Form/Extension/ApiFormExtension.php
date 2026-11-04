<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Extension;

use App\Form\API\ApiFormInterface;
use App\Form\Type\BillableType;
use App\Form\Type\YesNoType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adjusts form types for API usage, it is applied to all form types implementing the ApiFormInterface.
 *
 * @see ApiFormExtensionDecorator
 */
final class ApiFormExtension extends AbstractTypeExtension
{
    /**
     * The API documentation is always rendered in the default language.
     */
    private const DOCUMENTATION_LOCALE = 'en';

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Symfony cannot resolve interfaces here, the extension is registered by the ApiFormExtensionDecorator.
     */
    public static function getExtendedTypes(): iterable
    {
        return [ApiFormInterface::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($builder->has('metaFields')) {
            $builder->remove('metaFields');
        }

        /** @var array<int, array{name: string, label: string|bool|TranslatableInterface|null, required: bool, description: string|null}> $replaces */
        $replaces = [];

        foreach ($builder as $child) {
            if (\in_array($child->getType()->getInnerType()->getParent(), [CheckboxType::class, BillableType::class, YesNoType::class])) {
                $docs = $child->getFormConfig()->getOption('documentation');
                if (\is_array($docs) && \is_string($docs['description'] ?? null)) {
                    $description = $docs['description'];
                } else {
                    $description = $this->translateHelp($child->getFormConfig());
                }

                $replaces[] = [
                    'name' => $child->getName(),
                    'label' => $child->getFormConfig()->getOption('label'),
                    'required' => $child->getFormConfig()->getRequired(),
                    'description' => $description
                ];
            }
        }

        foreach ($replaces as $field) {
            $builder->remove($field['name']);
            $options = [
                'label' => $field['label'],
                'required' => $field['required'],
            ];

            if ($field['description'] !== null) {
                $options['documentation']['description'] = $field['description'];
            }

            $builder->add($field['name'], CheckboxType::class, $options);
        }
    }

    /**
     * Form help texts are translation keys, which are not readable in the API documentation.
     *
     * @param FormConfigInterface<mixed> $config
     */
    private function translateHelp(FormConfigInterface $config): ?string
    {
        $help = $config->getOption('help');

        if ($help instanceof TranslatableInterface) {
            return $help->trans($this->translator, self::DOCUMENTATION_LOCALE);
        }

        if (!\is_string($help) || $help === '') {
            return null;
        }

        $domain = $config->hasOption('help_translation_domain') ? $config->getOption('help_translation_domain') : null;
        $domain ??= $config->getOption('translation_domain');

        // translations were explicitly deactivated
        if ($domain === false) {
            return $help;
        }

        $parameters = $config->getOption('help_translation_parameters', []);

        return $this->translator->trans($help, \is_array($parameters) ? $parameters : [], \is_string($domain) ? $domain : null, self::DOCUMENTATION_LOCALE);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }
}
