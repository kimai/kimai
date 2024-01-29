<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Custom form field type to select the user locale, which is used to format date/time/money/number values.
 *
 * @extends AbstractType<string>
 */
final class UserLocaleType extends AbstractType
{
    public function __construct(
        private readonly UrlGeneratorInterface $router,
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $route = $this->router->generate('help_locales');
        $moreLink = $this->translator->trans('help_locales');

        $resolver->setDefaults([
            'label' => 'locale',
            'help_html' => true,
            'help' => sprintf('<a href="%1$s" target="help_locales">%2$s</a>', $route, $moreLink)
        ]);
    }

    public function getParent(): string
    {
        return LanguageType::class;
    }
}
