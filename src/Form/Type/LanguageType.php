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
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select the language.
 */
final class LanguageType extends AbstractType
{
    public function __construct(private LocaleService $localeService)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];
        foreach ($this->localeService->getAllLocales() as $key) {
            $name = ucfirst(Locales::getName($key, $key));
            $choices[$name] = $key;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'label' => 'language',
            'choice_translation_domain' => false,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
