<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Renderer;

use App\Invoice\InvoiceModel;
use App\Invoice\RendererInterface;
use App\Model\InvoiceDocument;
use App\Twig\LocaleFormatExtensions;
use App\Twig\SecurityPolicy\InvoicePolicy;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Twig\Environment;
use Twig\Extension\SandboxExtension;

/**
 * @internal
 */
abstract class AbstractTwigRenderer implements RendererInterface
{
    public function __construct(private readonly Environment $twig)
    {
    }

    protected function renderTwigTemplate(InvoiceDocument $document, InvoiceModel $model, array $options = []): string
    {
        $language = $model->getTemplate()->getLanguage();
        $formatLocale = $model->getFormatter()->getLocale();
        $template = '@invoice/' . basename($document->getFilename());
        $entries = [];
        foreach ($model->getCalculator()->getEntries() as $entry) {
            $entries[] = $model->itemToArray($entry);
        }

        $options = array_merge([
            // model should not be used in the future, but we can likely not remove it
            'model' => $model,
            // new since 1.16.7 - templates should only use the pre-generated values
            'invoice' => $model->toArray(),
            // new since 1.19.5 - templates should only use the pre-generated values
            'entries' => $entries
        ], $options);

        // cloning twig, because we don't want to change the
        return $this->renderTwigTemplateWithLanguage($this->twig, $template, $options, $language, $formatLocale);
    }

    private function renderTwigTemplateWithLanguage(Environment $twig, string $template, array $options = [], ?string $language = null, ?string $formatLocale = null): string
    {
        $previousTranslation = null;
        $previousFormatLocale = null;

        if ($language !== null) {
            $previousTranslation = $this->switchTranslationLocale($twig, $language);
        }
        if ($formatLocale !== null) {
            $previousFormatLocale = $this->switchFormatLocale($twig, $formatLocale);
        }

        if (!$twig->hasExtension(SandboxExtension::class)) {
            $twig->addExtension(new SandboxExtension(new InvoicePolicy()));
        }

        $sandbox = $twig->getExtension(SandboxExtension::class);
        $sandbox->enableSandbox();

        $content = $twig->render($template, $options);

        $sandbox->disableSandbox();

        if ($previousTranslation !== null) {
            $this->switchTranslationLocale($twig, $previousTranslation);
        }
        if ($previousFormatLocale !== null) {
            $this->switchFormatLocale($twig, $previousFormatLocale);
        }

        return $content;
    }

    private function switchTranslationLocale(Environment $twig, string $language): string
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

    private function switchFormatLocale(Environment $twig, string $language): string
    {
        /** @var LocaleFormatExtensions $extension */
        $extension = $twig->getExtension(LocaleFormatExtensions::class);
        $previous = $extension->getLocale();
        $extension->setLocale($language);

        return $previous;
    }
}
