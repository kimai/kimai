<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Renderer;

use App\Entity\InvoiceDocument;
use App\Invoice\InvoiceModel;
use App\Invoice\RendererInterface;
use App\Twig\LocaleFormatExtensions;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Twig\Environment;

/**
 * @internal
 */
abstract class AbstractTwigRenderer implements RendererInterface
{
    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    protected function renderTwigTemplate(InvoiceDocument $document, InvoiceModel $model): string
    {
        $previousLocale = $this->changeTwigLocale($this->twig, $model->getTemplate()->getLanguage());

        $content = $this->twig->render('@invoice/' . basename($document->getFilename()), [
            // model should not be used in the future, but we can likely not remove it
            'model' => $model,
            // new since 1.16.7 - templates should only use the pre-generated values
            'invoice' => $model->toArray(),
        ]);

        $this->changeTwigLocale($this->twig, $previousLocale);

        return $content;
    }

    private function changeTwigLocale(Environment $twig, ?string $locale = null): ?string
    {
        // for invoices that don't have a language configured (using request locale)
        if (null === $locale) {
            return null;
        }

        /** @var TranslationExtension $extension */
        $extension = $twig->getExtension(TranslationExtension::class);
        /** @var LocaleAwareInterface $translator */
        $translator = $extension->getTranslator();
        $previousLocale = $translator->getLocale();

        $translator->setLocale($locale);

        /** @var LocaleFormatExtensions $extension */
        $extension = $twig->getExtension(LocaleFormatExtensions::class);
        $extension->setLocale($locale);

        return $previousLocale;
    }
}
