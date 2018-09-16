<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Entity\InvoiceDocument;
use App\Invoice\Renderer\AbstractRenderer;
use App\Twig\DateExtensions;
use App\Twig\Extensions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractRendererTest extends TestCase
{
    protected function getInvoiceDocument($filename)
    {
        return new InvoiceDocument(
            new \SplFileInfo(__DIR__ . '/../../../templates/invoice/renderer/' . $filename)
        );
    }

    /**
     * @param $classname
     * @return AbstractRenderer
     */
    protected function getAbstractRenderer($classname)
    {
        $requestStack = new RequestStack();
        $dateSettings = [];
        $languages = [];

        $translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
        $dateExtension = new DateExtensions($requestStack, $dateSettings);
        $extensions = new Extensions($requestStack, $languages);

        return new $classname($translator, $dateExtension, $extensions);
    }
}
