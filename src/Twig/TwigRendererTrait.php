<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Twig\SecurityPolicy\InvoicePolicy;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Twig\Environment;
use Twig\Extension\SandboxExtension;

/**
 * @internal
 */
trait TwigRendererTrait
{
    protected function renderTwigTemplateWithLanguage(Environment $twig, string $template, array $options = [], ?string $language = null, ?string $formatLocale = null): string
    {
        $previousTranslation = null;
        $previousFormatLocale = null;

        if ($language !== null) {
            $previousTranslation = $this->switchTranslationLocale($twig, $language);
        }
        if ($formatLocale !== null) {
            $previousFormatLocale = $this->switchFormatLocale($twig, $formatLocale);
        }

        // enable basic security measures
        if (!$twig->hasExtension(SandboxExtension::class)) {
            $sandbox = new SandboxExtension(new InvoicePolicy());
            $sandbox->enableSandbox();
            $twig->addExtension($sandbox);
        }

        $content = $twig->render($template, $options);

        if ($previousTranslation !== null) {
            $this->switchTranslationLocale($twig, $previousTranslation);
        }
        if ($previousFormatLocale !== null) {
            $this->switchFormatLocale($twig, $previousFormatLocale);
        }

        return $content;
    }

    protected function switchTranslationLocale(Environment $twig, string $language): string
    {
        /** @var TranslationExtension $extension */
        $extension = $twig->getExtension(TranslationExtension::class);

        $translator = $extension->getTranslator();
        if (!$translator instanceof LocaleAwareInterface) {
            throw new \Exception('Translator is expected to be of type LocaleAwareInterface');
        }
        $previous = $translator->getLocale();
        $translator->setLocale($language);

        return $previous;
    }

    protected function switchFormatLocale(Environment $twig, string $language): string
    {
        /** @var LocaleFormatExtensions $extension */
        $extension = $twig->getExtension(LocaleFormatExtensions::class);
        $previous = $extension->getLocale();
        $extension->setLocale($language);

        return $previous;
    }
}
