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
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class TwigRenderer implements RendererInterface
{
    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function supports(InvoiceDocument $document): bool
    {
        return stripos($document->getFilename(), '.html.twig') !== false;
    }

    public function render(InvoiceDocument $document, InvoiceModel $model): Response
    {
        $content = $this->twig->render('@invoice/' . basename($document->getFilename()), [
            'model' => $model
        ]);

        $response = new Response();
        $response->setContent($content);
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');

        return $response;
    }
}
