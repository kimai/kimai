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
use App\Twig\TwigRendererTrait;
use Twig\Environment;

/**
 * @internal
 */
abstract class AbstractTwigRenderer implements RendererInterface
{
    use TwigRendererTrait;

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
        $language = $model->getTemplate()->getLanguage();
        $formatLocale = $model->getFormatter()->getLocale();
        $template = '@invoice/' . basename($document->getFilename());
        $options = [
            // model should not be used in the future, but we can likely not remove it
            'model' => $model,
            // new since 1.16.7 - templates should only use the pre-generated values
            'invoice' => $model->toArray(),
        ];

        return $this->renderTwigTemplateWithLanguage($this->twig, $template, $options, $language, $formatLocale);
    }
}
