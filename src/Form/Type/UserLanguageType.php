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
 * Custom form field type to select the user language.
 * @extends AbstractType<string>
 */
final class UserLanguageType extends AbstractType
{
    public function __construct(private UrlGeneratorInterface $router, private TranslatorInterface $translator)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $route = $this->router->generate('help_locales');
        $message = $this->translator->trans('user.language.help');
        $moreLink = $this->translator->trans('help_locales');

        $resolver->setDefaults([
            'help_html' => true,
            'help' => sprintf('%2$s <a href="%1$s" target="help_locales">%3$s</a>', $route, $message, $moreLink)
        ]);
    }

    public function getParent(): string
    {
        return LanguageType::class;
    }
}
