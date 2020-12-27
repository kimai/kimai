<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Twig\Environment;

trait EnvironmentTrait
{
    protected function changeLocale(Environment $twig, string $locale): string
    {
        /** @var TranslationExtension $extension */
        $extension = $twig->getExtension(TranslationExtension::class);
        /** @var LocaleAwareInterface $translator */
        $translator = $extension->getTranslator();
        $previousLocale = $translator->getLocale();

        $translator->setLocale($locale);

        /** @var LocaleExtensions $extension */
        $extension = $twig->getExtension(LocaleExtensions::class);
        $extension->setLocale($locale);

        /** @var DateExtensions $extension */
        $extension = $twig->getExtension(DateExtensions::class);
        $extension->setLocale($locale);

        return $previousLocale;
    }
}
