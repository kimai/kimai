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

class TwigRenderer implements RendererInterface
{
    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @param Environment $twig
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param InvoiceDocument $document
     * @return bool
     */
    public function supports(InvoiceDocument $document): bool
    {
        return stripos($document->getFilename(), '.twig') !== false;
    }

    /**
     * @param InvoiceDocument $document
     * @param InvoiceModel $model
     * @return Response
     */
    public function render(InvoiceDocument $document, InvoiceModel $model): Response
    {
        $content = $this->twig->render('@invoice/' . basename($document->getFilename()), [
            'model' => $model
        ]);

        $response = new Response();
        $response->setContent($content);

        return $response;
    }
}
