<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Invoice\Renderer\TwigRenderer;
use Twig\Loader\FilesystemLoader;

/**
 * @covers \App\Invoice\Renderer\TwigRenderer
 */
class TwigRendererTest extends AbstractRendererTest
{
    public function testSupports()
    {
        $loader = new FilesystemLoader();
        $env = new \Twig_Environment($loader);
        $sut = new TwigRenderer($env);

        $this->assertTrue($sut->supports($this->getInvoiceDocument('default.html.twig')));
        $this->assertTrue($sut->supports($this->getInvoiceDocument('freelancer.html.twig')));
        $this->assertTrue($sut->supports($this->getInvoiceDocument('timesheet.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('foo.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('company.docx')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('export.csv')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('spreadsheet.xlsx')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('open-spreadsheet.ods')));
    }
}
