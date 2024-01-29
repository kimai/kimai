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
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select the user language, which is used to translate the UI.
 * @extends AbstractType<string>
 */
final class UserLanguageType extends AbstractType
{
    public function __construct(private readonly LocaleService $localeService)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($value) {
                if ($value === null) {
                    return null;
                }

                return $this->localeService->getNearestTranslationLocale($value);
            },
            function ($value) {
                return $value;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'language',
            'translated_only' => true,
        ]);
    }

    public function getParent(): string
    {
        return LanguageType::class;
    }
}
