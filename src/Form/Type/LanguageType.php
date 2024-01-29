<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Configuration\LocaleService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Intl\Locales;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select the language.
 */
final class LanguageType extends AbstractType
{
    public function __construct(private readonly LocaleService $localeService)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('choices', function (Options $options) {
            $choices = [];

            if ($options['translated_only'] === true) {
                $locales = $this->localeService->getTranslatedLocales();
            } else {
                $locales = $this->localeService->getAllLocales();
            }

            foreach ($locales as $key) {
                $name = ucfirst(Locales::getName($key, $key));
                $choices[$name] = $key;
            }

            return $choices;
        });

        $resolver->setDefaults([
            'label' => 'language',
            'translated_only' => false,
            'choice_translation_domain' => false,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
